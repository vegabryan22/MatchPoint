<?php

namespace App\Services;

use App\Enums\DrawMethod;
use App\Enums\MatchStatus;
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
        $registeredParticipants = $this->participants($tournament);
        $selectedIds = array_map('intval', $data['selected_participants'] ?? $data['resolved_order'] ?? $registeredParticipants->pluck('id')->all());
        $this->validateSelectedParticipants($registeredParticipants, $selectedIds);
        $participants = $registeredParticipants->whereIn('id', $selectedIds)->values();
        $data['seeds'] = collect($data['seeds'] ?? [])->only($selectedIds)->all();

        if ($participants->count() < 2) {
            throw ValidationException::withMessages(['draw' => 'Se necesitan al menos dos participantes inscritos.']);
        }

        if ($tournament->format === TournamentFormat::SingleElimination && $participants->count() % 2 !== 0) {
            throw ValidationException::withMessages([
                'draw' => 'Para que todos jueguen la ronda clasificatoria, la cantidad de inscritos debe ser par.',
            ]);
        }

        if ($tournament->participant_type === ParticipantType::Team) {
            $participants->load('players');
        }

        $method = DrawMethod::from($data['method']);
        $order = isset($data['resolved_order'])
            ? array_map('intval', $data['resolved_order'])
            : $this->strategies->resolve($method)->order($tournament, $participants, $data);
        $this->validateResolvedOrder($participants, $order);
        $manualPairing = $method === DrawMethod::Manual && (bool) ($data['manual_pairing'] ?? false);
        $pairing = $this->pairings->pair($tournament, $order, (bool) ($data['avoid_rematches'] ?? false), $manualPairing);
        $participantMap = $participants->keyBy('id');
        $hydratePairs = fn (array $pairs): array => collect($pairs)->map(fn (array $pair): array => [
            'participant_a_id' => $pair[0],
            'participant_b_id' => $pair[1],
            'participant_a' => $participantMap->get($pair[0]),
            'participant_b' => $pair[1] === null ? null : $participantMap->get($pair[1]),
        ])->all();

        return [
            'method' => $method,
            'generation_mode' => $data['generation_mode'] ?? 'replace',
            'batch_name' => trim((string) ($data['batch_name'] ?? '')),
            'avoid_rematches' => (bool) ($data['avoid_rematches'] ?? false),
            'manual_pairing' => $manualPairing,
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
            'best_loser_count' => $pairing['best_loser_count'] ?? 0,
            'repechage' => $pairing['repechage'] ?? false,
        ];
    }

    public function generate(Tournament $tournament, array $data, User $actor): void
    {
        $plan = $this->preview($tournament, $data);

        DB::transaction(function () use ($tournament, $plan, $actor): void {
            $lockedTournament = Tournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();
            $mode = $plan['generation_mode'];

            if ($mode === 'replace' && $this->draws->hasCompletedMatches($lockedTournament)) {
                throw ValidationException::withMessages(['draw' => 'No se puede regenerar un sorteo con resultados registrados.']);
            }

            if ($mode === 'append') {
                $this->ensureNewBatchIsAllowed($lockedTournament);
                $lockedTournament->champion()->delete();
            } elseif ($mode === 'final') {
                $this->ensureFinalistsAreBatchWinners($lockedTournament, $plan['order']);
            } else {
                $this->draws->deleteArtifacts($lockedTournament);
                $this->draws->clearSeeds($lockedTournament);
            }

            $batchNumber = ((int) $lockedTournament->draws()->max('batch_number')) + 1;
            $version = ((int) $lockedTournament->draws()->max('version')) + 1;
            $repeatedParticipantIds = $mode === 'append'
                ? collect($plan['order'])->intersect($this->usedParticipantIds($lockedTournament))->values()->all()
                : [];
            if ($mode === 'replace') {
                $this->draws->updateSeeds($lockedTournament, $plan['order']);
            }
            $draw = $this->draws->createDraw([
                'tournament_id' => $lockedTournament->id,
                'batch_number' => $batchNumber,
                'name' => $plan['batch_name'] !== '' ? $plan['batch_name'] : ($mode === 'final' ? 'Final de tandas' : 'Tanda '.$batchNumber),
                'is_final_stage' => $mode === 'final',
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
                    'best_loser_count' => $plan['best_loser_count'],
                    'repechage' => $plan['repechage'],
                    'main_matches' => $plan['main_matches'],
                    'manual_pairing' => $plan['manual_pairing'],
                    'active_participant_ids' => $plan['order'],
                    'repeated_participant_ids' => $repeatedParticipantIds,
                ],
                'generated_at' => now(),
            ]);
            $this->brackets->generate($lockedTournament, $plan, $draw);

            $this->audit->record('draw.generated', $lockedTournament, [], [
                'method' => $plan['method']->value,
                'version' => $version,
                'avoid_rematches' => $plan['avoid_rematches'],
                'manual_pairing' => $plan['manual_pairing'],
                'participant_order' => $plan['order'],
                'batch_number' => $batchNumber,
                'generation_mode' => $mode,
                'repeated_participant_ids' => $repeatedParticipantIds,
            ], $actor->id);
        });
    }

    public function reset(Tournament $tournament, User $actor, ?int $drawId = null): void
    {
        DB::transaction(function () use ($tournament, $actor, $drawId): void {
            $lockedTournament = Tournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();

            if ($lockedTournament->status === TournamentStatus::Finished) {
                throw ValidationException::withMessages(['draw' => 'No se pueden eliminar llaves de un torneo finalizado.']);
            }

            $draw = $drawId === null
                ? $lockedTournament->draws()->reorder()->orderByDesc('batch_number')->first()
                : $lockedTournament->draws()->whereKey($drawId)->first();

            if ($draw === null) {
                throw ValidationException::withMessages(['draw' => 'La tanda seleccionada no existe.']);
            }

            if ($draw->matches()->where('status', MatchStatus::Completed)->exists()) {
                throw ValidationException::withMessages(['draw' => 'No se puede eliminar una tanda con resultados registrados.']);
            }

            $snapshot = $draw->toArray();
            $draw->delete();
            if (! $lockedTournament->draws()->exists()) {
                $this->draws->clearSeeds($lockedTournament);
            }
            $this->audit->record('draw.reset', $lockedTournament, $snapshot, [], $actor->id);
        });
    }

    public function details(Tournament $tournament, ?int $drawId = null): array
    {
        $tournament->load(['draws.generator', 'draws.rounds.matches.scores', 'draws.rounds.matches.station', 'rounds.matches.scores', 'rounds.matches.station', 'champion']);
        $legacyRounds = $tournament->rounds;
        $selectedDraw = $drawId === null
            ? $tournament->draws->last()
            : $tournament->draws->firstWhere('id', $drawId);
        if ($drawId !== null && $selectedDraw === null) {
            abort(404);
        }
        $tournament->setRelation('draw', $selectedDraw);
        $tournament->setRelation('rounds', $selectedDraw?->rounds ?? $legacyRounds);
        $participants = $this->participants($tournament)->keyBy('id');
        $clubs = GameClub::query()->whereIn('id', $participants->pluck('pivot.game_club_id')->filter()->unique())->get()->keyBy('id');
        $qualificationProgress = null;
        if ($tournament->draw?->metadata['repechage'] ?? false) {
            $qualificationRound = $tournament->rounds
                ->first(fn ($round): bool => $round->bracket->value === 'main' && $round->number === 1);
            $qualificationProgress = [
                'completed' => $qualificationRound?->matches->where('status', MatchStatus::Completed)->count() ?? 0,
                'total' => $qualificationRound?->matches->count() ?? 0,
                'best_loser_count' => (int) ($tournament->draw->metadata['best_loser_count'] ?? 0),
            ];
        }

        return [
            'tournament' => $tournament,
            'drawBatches' => $tournament->draws,
            'selectedDraw' => $selectedDraw,
            'participantsById' => $participants,
            'activeParticipantCount' => count($tournament->draw?->metadata['active_participant_ids'] ?? $participants->keys()->all()),
            'qualificationProgress' => $qualificationProgress,
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

    private function validateSelectedParticipants(Collection $registeredParticipants, array $selectedIds): void
    {
        $registeredIds = $registeredParticipants->pluck('id')->map(fn ($id): int => (int) $id);
        $selected = collect($selectedIds);

        if ($selected->count() < 2 || $selected->duplicates()->isNotEmpty() || $selected->diff($registeredIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'selected_participants' => 'Selecciona al menos dos participantes inscritos sin repetirlos.',
            ]);
        }
    }

    private function ensureNewBatchIsAllowed(Tournament $tournament): void
    {
        if ($tournament->draws()->where('is_final_stage', true)->exists()) {
            throw ValidationException::withMessages([
                'draw' => 'No se pueden agregar tandas después de crear la final entre ganadores.',
            ]);
        }

    }

    private function usedParticipantIds(Tournament $tournament): \Illuminate\Support\Collection
    {
        return $tournament->draws()->get()->flatMap(
            fn ($draw) => $draw->metadata['active_participant_ids'] ?? [],
        )->map(fn ($id): int => (int) $id)->unique();
    }

    private function ensureFinalistsAreBatchWinners(Tournament $tournament, array $participantIds): void
    {
        if ($tournament->draws()->where('is_final_stage', true)->exists()) {
            throw ValidationException::withMessages(['draw' => 'La final entre ganadores ya fue creada.']);
        }

        $batches = $tournament->draws()->where('is_final_stage', false)->get();
        if ($batches->count() < 2 || $batches->contains(fn ($draw): bool => $draw->winner_id === null)) {
            throw ValidationException::withMessages(['draw' => 'Todas las tandas deben estar finalizadas antes de crear la final.']);
        }

        $winnerIds = $batches->pluck('winner_id');
        $selected = collect($participantIds)->sort()->values()->all();
        $expected = $winnerIds->map(fn ($id): int => (int) $id)->sort()->values()->all();

        if ($selected !== $expected) {
            throw ValidationException::withMessages([
                'selected_participants' => 'La final debe incluir exactamente a los ganadores de todas las tandas finalizadas.',
            ]);
        }
    }
}
