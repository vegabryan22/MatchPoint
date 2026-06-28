<?php

namespace App\Services;

use App\Enums\BracketType;
use App\Enums\MatchSlot;
use App\Enums\MatchStatus;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Events\MatchCompleted;
use App\Models\GameMatch;
use App\Models\User;
use App\Repositories\Contracts\GameMatchRepositoryInterface;
use App\Repositories\Contracts\MatchResultRepositoryInterface;
use App\Repositories\Contracts\TournamentRegistrationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MatchResultService
{
    public function __construct(
        private readonly GameMatchRepositoryInterface $matches,
        private readonly MatchResultRepositoryInterface $results,
        private readonly TournamentRegistrationRepositoryInterface $registrations,
        private readonly AuditService $audit,
    ) {}

    public function details(GameMatch $match): array
    {
        $match->load(['tournament', 'round', 'scores', 'completedBy']);
        $participants = $this->registrations->all($match->tournament)->keyBy('id');

        return [
            'match' => $match,
            'participantA' => $participants->get($match->participant_a_id),
            'participantB' => $participants->get($match->participant_b_id),
        ];
    }

    public function record(GameMatch $match, array $data, User $actor): GameMatch
    {
        return $this->persist($match, $data, $actor, false);
    }

    public function correct(GameMatch $match, array $data, User $actor): GameMatch
    {
        return $this->persist($match, $data, $actor, true);
    }

    private function persist(GameMatch $match, array $data, User $actor, bool $correction): GameMatch
    {
        return DB::transaction(function () use ($match, $data, $actor, $correction): GameMatch {
            $lockedMatch = $this->matches->findForUpdate($match->id);
            $lockedMatch->load(['tournament', 'round', 'scores']);
            $this->ensureRecordable($lockedMatch, $correction);

            $oldResult = $this->resultSnapshot($lockedMatch);
            if ($correction) {
                $this->removePreviousAdvancement($lockedMatch);
            }

            $series = $this->resolveSeries($lockedMatch, $data['games']);
            $this->results->replaceScores($lockedMatch, $series['games'], $actor->id);
            $lockedMatch = $this->matches->update($lockedMatch, [
                'winner_id' => $series['winner_id'],
                'status' => MatchStatus::Completed,
                'duration_seconds' => isset($data['duration_minutes']) ? ((int) $data['duration_minutes'] * 60) : null,
                'observations' => $data['observations'] ?? null,
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            $newResult = [
                ...$this->resultSnapshot($lockedMatch->load('scores')),
                'series_wins' => [$series['participant_a_wins'], $series['participant_b_wins']],
            ];
            $this->audit->record(
                $correction ? 'match.result_corrected' : 'match.result_recorded',
                $lockedMatch,
                $oldResult,
                $newResult,
                $actor->id,
            );

            MatchCompleted::dispatch($lockedMatch->id, $actor->id);

            return $lockedMatch->fresh(['scores', 'completedBy']);
        });
    }

    private function ensureRecordable(GameMatch $match, bool $correction): void
    {
        if ($match->tournament->status !== TournamentStatus::InProgress) {
            throw ValidationException::withMessages(['match' => 'El torneo debe estar en curso para registrar resultados.']);
        }

        if ($match->participant_a_id === null || $match->participant_b_id === null) {
            throw ValidationException::withMessages(['match' => 'El partido todavía no tiene ambos participantes definidos.']);
        }

        $expectedStatus = $correction ? MatchStatus::Completed : MatchStatus::Pending;
        if ($match->status !== $expectedStatus) {
            $message = $correction
                ? 'Sólo se pueden corregir partidos finalizados.'
                : 'Sólo se pueden registrar resultados de partidos pendientes.';
            throw ValidationException::withMessages(['match' => $message]);
        }

        if ($correction) {
            $this->ensureDestinationsAreSafe($match);
        }
    }

    private function resolveSeries(GameMatch $match, array $submittedGames): array
    {
        $games = [];
        foreach ($submittedGames as $game) {
            $aScore = $game['participant_a_score'] ?? null;
            $bScore = $game['participant_b_score'] ?? null;

            if ($aScore === null && $bScore === null) {
                continue;
            }
            if ($aScore === null || $bScore === null) {
                throw ValidationException::withMessages(['games' => 'Cada juego debe incluir ambos marcadores.']);
            }

            $games[] = ['participant_a_score' => (int) $aScore, 'participant_b_score' => (int) $bScore];
        }

        $bestOf = $match->best_of->value;
        $winsRequired = intdiv($bestOf, 2) + 1;
        if ($games === [] || count($games) > $bestOf) {
            throw ValidationException::withMessages(['games' => "La serie {$match->best_of->label()} admite entre 1 y {$bestOf} juegos."]);
        }

        $participantAWins = 0;
        $participantBWins = 0;
        $normalizedGames = [];
        $drawAllowed = $match->round?->bracket === BracketType::Group
            && in_array($match->tournament->format, [TournamentFormat::RoundRobin, TournamentFormat::League, TournamentFormat::GroupsKnockout], true)
            && $bestOf === 1;

        foreach ($games as $index => $game) {
            if ($game['participant_a_score'] === $game['participant_b_score']) {
                if (! $drawAllowed) {
                    throw ValidationException::withMessages(['games' => 'Los juegos eliminatorios no pueden terminar empatados.']);
                }
                $normalizedGames[] = ['game_number' => 1, ...$game, 'winner_id' => null];

                continue;
            }

            $winnerId = $game['participant_a_score'] > $game['participant_b_score']
                ? $match->participant_a_id
                : $match->participant_b_id;
            $winnerId === $match->participant_a_id ? $participantAWins++ : $participantBWins++;
            $normalizedGames[] = [
                'game_number' => $index + 1,
                ...$game,
                'winner_id' => $winnerId,
            ];

            $seriesFinished = max($participantAWins, $participantBWins) === $winsRequired;
            if ($seriesFinished && $index !== array_key_last($games)) {
                throw ValidationException::withMessages(['games' => 'No se permiten juegos adicionales después de definir la serie.']);
            }
        }

        if (! $drawAllowed && max($participantAWins, $participantBWins) !== $winsRequired) {
            throw ValidationException::withMessages(['games' => "La serie requiere {$winsRequired} victorias para definir al ganador."]);
        }

        return [
            'games' => $normalizedGames,
            'winner_id' => $participantAWins === $participantBWins
                ? null
                : ($participantAWins > $participantBWins ? $match->participant_a_id : $match->participant_b_id),
            'participant_a_wins' => $participantAWins,
            'participant_b_wins' => $participantBWins,
        ];
    }

    private function ensureDestinationsAreSafe(GameMatch $match): void
    {
        foreach (array_filter([$match->winner_next_match_id, $match->loser_next_match_id]) as $destinationId) {
            $destination = $this->matches->findForUpdate($destinationId);
            if ($destination->status !== MatchStatus::Pending || $destination->winner_id !== null) {
                throw ValidationException::withMessages([
                    'match' => 'No se puede corregir porque un partido dependiente ya avanzó o fue finalizado.',
                ]);
            }
        }

        $this->ensureConditionalResetIsSafe($match);
    }

    private function removePreviousAdvancement(GameMatch $match): void
    {
        $this->clearDestination($match->winner_next_match_id, $match->winner_next_slot, $match->winner_id);
        $this->clearDestination($match->loser_next_match_id, $match->loser_next_slot, $match->loserId());

        if ($this->isGrandFinal($match)) {
            $reset = GameMatch::query()
                ->where('tournament_id', $match->tournament_id)
                ->where('is_conditional', true)
                ->lockForUpdate()
                ->first();
            if ($reset !== null) {
                $this->matches->update($reset, [
                    'participant_a_id' => null,
                    'participant_b_id' => null,
                    'winner_id' => null,
                    'status' => MatchStatus::Pending,
                ]);
            }
        }
    }

    private function clearDestination(?int $destinationId, ?MatchSlot $slot, ?int $participantId): void
    {
        if ($destinationId === null || $slot === null || $participantId === null) {
            return;
        }

        $destination = $this->matches->findForUpdate($destinationId);
        $column = $slot === MatchSlot::A ? 'participant_a_id' : 'participant_b_id';
        if ($destination->{$column} !== $participantId) {
            throw ValidationException::withMessages(['match' => 'La llave cambió y el resultado ya no puede corregirse de forma segura.']);
        }

        $this->matches->update($destination, [$column => null]);
    }

    private function ensureConditionalResetIsSafe(GameMatch $match): void
    {
        if (! $this->isGrandFinal($match)) {
            return;
        }

        $reset = GameMatch::query()
            ->where('tournament_id', $match->tournament_id)
            ->where('is_conditional', true)
            ->lockForUpdate()
            ->first();
        if ($reset !== null && $reset->status === MatchStatus::Completed) {
            throw ValidationException::withMessages(['match' => 'La final de reinicio ya fue finalizada y bloquea esta corrección.']);
        }
    }

    private function isGrandFinal(GameMatch $match): bool
    {
        return $match->round?->bracket === BracketType::Finals && $match->round->number === 1;
    }

    private function resultSnapshot(GameMatch $match): array
    {
        return [
            'winner_id' => $match->winner_id,
            'status' => $match->status->value,
            'duration_seconds' => $match->duration_seconds,
            'observations' => $match->observations,
            'scores' => $match->scores->map(fn ($score): array => [
                'game_number' => $score->game_number,
                'participant_a_score' => $score->participant_a_score,
                'participant_b_score' => $score->participant_b_score,
            ])->all(),
        ];
    }
}
