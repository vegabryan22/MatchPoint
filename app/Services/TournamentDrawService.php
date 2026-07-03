<?php

namespace App\Services;

use App\Enums\DrawMethod;
use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\GameClub;
use App\Models\Tournament;
use App\Models\User;
use App\Repositories\Contracts\TournamentDrawRepositoryInterface;
use App\Repositories\Contracts\TournamentRegistrationRepositoryInterface;
use App\Services\Draw\RematchAwarePairingService;
use App\Services\Draw\SeedingStrategyResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TournamentDrawService
{
    public function __construct(
        private readonly TournamentRegistrationRepositoryInterface $registrations,
        private readonly TournamentDrawRepositoryInterface $draws,
        private readonly SeedingStrategyResolver $strategies,
        private readonly RematchAwarePairingService $pairings,
        private readonly BracketGenerationService $brackets,
        private readonly AuditService $audit,
        private readonly BracketPresentationService $presentation,
    ) {}

    public function participants(Tournament $tournament): Collection
    {
        return $this->registrations->all($tournament);
    }

    public function preview(Tournament $tournament, array $data): array
    {
        $this->ensureDrawable($tournament);
        $participants = $this->participants($tournament);

        if ($participants->count() < 2) {
            throw ValidationException::withMessages(['draw' => 'Se necesitan al menos dos participantes inscritos.']);
        }

        if ($tournament->participant_type === ParticipantType::Team) {
            $participants->load('players');
        }

        $method = DrawMethod::from($data['method']);
        $order = isset($data['resolved_order'])
            ? array_map('intval', $data['resolved_order'])
            : $this->strategies->resolve($method)->order($tournament, $participants, $data);
        $this->validateResolvedOrder($participants, $order);
        $pairing = $this->pairings->pair($tournament, $order, (bool) ($data['avoid_rematches'] ?? false));
        $participantMap = $participants->keyBy('id');
        $hydratePairs = fn (array $pairs): array => collect($pairs)->map(fn (array $pair): array => [
            'participant_a_id' => $pair[0],
            'participant_b_id' => $pair[1],
            'participant_a' => $participantMap->get($pair[0]),
            'participant_b' => $pair[1] === null ? null : $participantMap->get($pair[1]),
        ])->all();

        return [
            'method' => $method,
            'avoid_rematches' => (bool) ($data['avoid_rematches'] ?? false),
            'order' => $order,
            'seeded_participants' => collect($order)->map(fn (int $id, int $index): array => [
                'seed' => $index + 1,
                'participant' => $participantMap->get($id),
            ])->all(),
            'pairs' => $hydratePairs($pairing['pairs']),
            'preliminary_pairs' => $hydratePairs($pairing['preliminary_pairs'] ?? []),
            'main_matches' => $pairing['main_matches'] ?? null,
            'direct_participant_ids' => $pairing['direct_participant_ids'] ?? [],
            'bracket_size' => $pairing['bracket_size'],
            'bye_count' => $pairing['bye_count'],
            'preliminary_count' => $pairing['preliminary_count'] ?? 0,
        ];
    }

    public function generate(Tournament $tournament, array $data, User $actor): void
    {
        $plan = $this->preview($tournament, $data);

        DB::transaction(function () use ($tournament, $plan, $actor): void {
            $lockedTournament = Tournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();

            if ($this->draws->hasCompletedMatches($lockedTournament)) {
                throw ValidationException::withMessages(['draw' => 'No se puede regenerar un sorteo con resultados registrados.']);
            }

            $version = ($lockedTournament->draw?->version ?? 0) + 1;
            $this->draws->deleteArtifacts($lockedTournament);
            $this->draws->clearSeeds($lockedTournament);
            $this->draws->updateSeeds($lockedTournament, $plan['order']);
            $this->draws->createDraw([
                'tournament_id' => $lockedTournament->id,
                'generated_by' => $actor->id,
                'method' => $plan['method'],
                'avoid_rematches' => $plan['avoid_rematches'],
                'version' => $version,
                'metadata' => [
                    'order' => $plan['order'],
                    'pairs' => collect($plan['pairs'])->map(fn ($pair): array => [$pair['participant_a_id'], $pair['participant_b_id']])->all(),
                    'bracket_size' => $plan['bracket_size'],
                    'bye_count' => $plan['bye_count'],
                    'preliminary_count' => $plan['preliminary_count'],
                    'main_matches' => $plan['main_matches'],
                ],
                'generated_at' => now(),
            ]);
            $this->brackets->generate($lockedTournament, $plan);

            $this->audit->record('draw.generated', $lockedTournament, [], [
                'method' => $plan['method']->value,
                'version' => $version,
                'avoid_rematches' => $plan['avoid_rematches'],
                'participant_order' => $plan['order'],
            ], $actor->id);
        });
    }

    public function reset(Tournament $tournament, User $actor): void
    {
        DB::transaction(function () use ($tournament, $actor): void {
            $lockedTournament = Tournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();

            if ($this->draws->hasCompletedMatches($lockedTournament)) {
                throw ValidationException::withMessages(['draw' => 'No se puede eliminar un sorteo con resultados registrados.']);
            }

            $this->draws->deleteArtifacts($lockedTournament);
            $this->draws->clearSeeds($lockedTournament);
            $this->audit->record('draw.reset', $lockedTournament, [], [], $actor->id);
        });
    }

    public function details(Tournament $tournament): array
    {
        $tournament->load(['draw.generator', 'rounds.matches.scores', 'rounds.matches.station', 'champion']);
        $participants = $this->participants($tournament)->keyBy('id');
        $clubs = GameClub::query()->whereIn('id', $participants->pluck('pivot.game_club_id')->filter()->unique())->get()->keyBy('id');

        return [
            'tournament' => $tournament,
            'participantsById' => $participants,
            ...$this->presentation->present($tournament, $participants, $clubs),
        ];
    }

    private function ensureDrawable(Tournament $tournament): void
    {
        if (! in_array($tournament->status, [TournamentStatus::Registration, TournamentStatus::InProgress], true)) {
            throw ValidationException::withMessages(['draw' => 'El torneo debe estar en Inscripciones o En curso.']);
        }

        if (! in_array($tournament->format, [TournamentFormat::SingleElimination, TournamentFormat::DoubleElimination], true)) {
            throw ValidationException::withMessages([
                'draw' => 'Este módulo genera llaves para eliminación simple o doble. Los demás formatos se programarán en el módulo de grupos y liga.',
            ]);
        }
    }

    private function validateResolvedOrder(Collection $participants, array $order): void
    {
        $expected = $participants->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
        $actual = collect($order)->sort()->values()->all();

        if ($expected !== $actual || count($order) !== count(array_unique($order))) {
            throw ValidationException::withMessages(['resolved_order' => 'El orden no coincide con los participantes inscritos.']);
        }
    }
}
