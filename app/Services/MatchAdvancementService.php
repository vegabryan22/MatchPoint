<?php

namespace App\Services;

use App\Enums\BracketType;
use App\Enums\MatchSlot;
use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Repositories\Contracts\GameMatchRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MatchAdvancementService
{
    public function __construct(
        private readonly GameMatchRepositoryInterface $matches,
        private readonly AuditService $audit,
        private readonly PreliminaryQualificationService $qualification,
    ) {}

    public function advance(GameMatch|int $match, ?int $actorId = null): void
    {
        $matchId = $match instanceof GameMatch ? $match->id : $match;

        DB::transaction(function () use ($matchId, $actorId): void {
            $this->advanceLocked($this->matches->findForUpdate($matchId), $actorId);
        });
    }

    private function advanceLocked(GameMatch $match, ?int $actorId): void
    {
        $match->loadMissing('round');
        if ($match->round?->bracket === BracketType::Group && $match->status === MatchStatus::Completed) {
            return;
        }

        if (! in_array($match->status, [MatchStatus::Completed, MatchStatus::Bye], true)) {
            throw ValidationException::withMessages(['match' => 'Sólo pueden avanzar partidos finalizados o pases automáticos.']);
        }

        if ($match->winner_id === null || ! in_array($match->winner_id, [$match->participant_a_id, $match->participant_b_id], true)) {
            throw ValidationException::withMessages(['winner_id' => 'El ganador debe ser uno de los participantes del partido.']);
        }

        if ($this->qualification->handle($match, $actorId)) {
            return;
        }

        $changedDestinations = [];
        if ($match->winner_next_match_id !== null && $match->winner_next_slot !== null) {
            $changedDestinations[] = $this->placeParticipant(
                $match->winner_next_match_id,
                $match->winner_next_slot,
                $match->winner_id,
            );
        }

        $loserId = $match->loserId();
        if ($loserId !== null && $match->loser_next_match_id !== null && $match->loser_next_slot !== null) {
            $changedDestinations[] = $this->placeParticipant(
                $match->loser_next_match_id,
                $match->loser_next_slot,
                $loserId,
            );
        }

        $changedDestinationIds = array_values(array_unique(array_filter($changedDestinations)));
        foreach ($changedDestinationIds as $destinationId) {
            $this->settleDestination($destinationId, $actorId);
        }

        $this->handleConditionalReset($match);

        if ($match->status === MatchStatus::Completed && $changedDestinationIds !== []) {
            $this->audit->record('match.advanced', $match, [], [
                'winner_id' => $match->winner_id,
                'winner_next_match_id' => $match->winner_next_match_id,
                'loser_next_match_id' => $match->loser_next_match_id,
            ], $actorId);
        }
    }

    private function placeParticipant(int $destinationId, MatchSlot $slot, int $participantId): ?int
    {
        $destination = $this->matches->findForUpdate($destinationId);
        $column = $slot === MatchSlot::A ? 'participant_a_id' : 'participant_b_id';
        $currentParticipant = $destination->{$column};

        if ($currentParticipant === $participantId) {
            return null;
        }

        if ($currentParticipant !== null) {
            throw ValidationException::withMessages(['match' => 'El espacio de destino ya pertenece a otro participante.']);
        }

        $this->matches->update($destination, [$column => $participantId]);

        return $destinationId;
    }

    private function settleDestination(int $destinationId, ?int $actorId): void
    {
        $destination = $this->matches->findForUpdate($destinationId);
        $feeders = $this->matches->feedersFor($destination);
        $resolvedStatuses = [MatchStatus::Completed, MatchStatus::Bye, MatchStatus::Cancelled];

        if ($feeders->contains(fn (GameMatch $feeder): bool => ! in_array($feeder->status, $resolvedStatuses, true))) {
            return;
        }

        if ($destination->participant_a_id !== null && $destination->participant_b_id !== null) {
            if ($destination->status !== MatchStatus::Pending) {
                $this->matches->update($destination, ['status' => MatchStatus::Pending, 'winner_id' => null]);
            }

            return;
        }

        $participantId = $destination->participant_a_id ?? $destination->participant_b_id;
        if ($participantId === null) {
            $this->matches->update($destination, ['status' => MatchStatus::Cancelled, 'winner_id' => null]);

            return;
        }

        $destination = $this->matches->update($destination, [
            'status' => MatchStatus::Bye,
            'winner_id' => $participantId,
        ]);
        $this->advanceLocked($destination, $actorId);
    }

    private function handleConditionalReset(GameMatch $match): void
    {
        $match->loadMissing('round');
        if ($match->round?->bracket !== BracketType::Finals || $match->round->number !== 1) {
            return;
        }

        $reset = GameMatch::query()
            ->where('tournament_id', $match->tournament_id)
            ->where('is_conditional', true)
            ->lockForUpdate()
            ->first();

        if ($reset === null) {
            return;
        }

        if ($match->winner_id === $match->participant_a_id) {
            $this->matches->update($reset, ['status' => MatchStatus::Cancelled]);

            return;
        }

        $this->matches->update($reset, [
            'participant_a_id' => $match->participant_a_id,
            'participant_b_id' => $match->participant_b_id,
            'winner_id' => null,
            'status' => MatchStatus::Pending,
        ]);
    }
}
