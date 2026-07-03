<?php

namespace App\Services;

use App\Enums\BracketType;
use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Models\GameMatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PreliminaryQualificationService
{
    public function __construct(private readonly AuditService $audit) {}

    public function handle(GameMatch $match, ?int $actorId): bool
    {
        $match->loadMissing(['round', 'tournament.draw']);
        $metadata = $match->tournament->draw?->metadata ?? [];

        if (! ($metadata['repechage'] ?? false)
            || $match->round?->bracket !== BracketType::Main
            || $match->round->number !== 1) {
            return false;
        }

        $qualifyingMatches = GameMatch::query()
            ->where('round_id', $match->round_id)
            ->with('scores')
            ->orderBy('sequence')
            ->lockForUpdate()
            ->get();

        if ($qualifyingMatches->contains(fn (GameMatch $candidate): bool => $candidate->status !== MatchStatus::Completed)) {
            return true;
        }

        $mainRound = $match->tournament->rounds()
            ->where('bracket', BracketType::Main)
            ->where('number', 2)
            ->firstOrFail();
        $mainMatches = $mainRound->matches()->with('scores')->lockForUpdate()->get();

        if ($mainMatches->contains(fn (GameMatch $candidate): bool => $candidate->status === MatchStatus::Completed || $candidate->scores->isNotEmpty())) {
            throw ValidationException::withMessages([
                'match' => 'No se puede recalcular la clasificación porque la ronda principal ya tiene resultados.',
            ]);
        }

        $bestLoserCount = (int) ($metadata['best_loser_count'] ?? 0);
        $seedMap = $this->seedMap($match);
        $winners = $qualifyingMatches->map(fn (GameMatch $candidate): array => [
            'participant_id' => $candidate->winner_id,
            'sequence' => $candidate->sequence,
        ])->values();
        $rankedLosers = $qualifyingMatches
            ->map(fn (GameMatch $candidate): array => $this->loserRow($candidate, $seedMap))
            ->sort(function (array $first, array $second): int {
                return $second['goal_difference'] <=> $first['goal_difference']
                    ?: $second['goals_for'] <=> $first['goals_for']
                    ?: $first['seed'] <=> $second['seed']
                    ?: $first['participant_id'] <=> $second['participant_id'];
            })
            ->values();
        $selectedLosers = $rankedLosers->take($bestLoserCount);
        $pairs = $this->mainPairs($winners, $selectedLosers);

        foreach ($mainMatches as $index => $destination) {
            $pair = $pairs[$index];
            $destination->update([
                'participant_a_id' => $pair[0],
                'participant_b_id' => $pair[1],
                'winner_id' => null,
                'status' => MatchStatus::Pending,
            ]);
        }

        $this->audit->record('draw.repechage_qualified', $match->tournament, [], [
            'winners' => $winners->pluck('participant_id')->all(),
            'best_losers' => $selectedLosers->pluck('participant_id')->all(),
            'ranking' => $rankedLosers->all(),
        ], $actorId);

        return true;
    }

    private function loserRow(GameMatch $match, Collection $seedMap): array
    {
        $loserId = $match->loserId();
        $participantAScore = $match->scores->sum('participant_a_score');
        $participantBScore = $match->scores->sum('participant_b_score');
        $loserIsA = $loserId === $match->participant_a_id;
        $goalsFor = $loserIsA ? $participantAScore : $participantBScore;
        $goalsAgainst = $loserIsA ? $participantBScore : $participantAScore;

        return [
            'participant_id' => $loserId,
            'lost_to' => $match->winner_id,
            'goal_difference' => $goalsFor - $goalsAgainst,
            'goals_for' => $goalsFor,
            'seed' => (int) ($seedMap->get($loserId) ?? PHP_INT_MAX),
        ];
    }

    private function seedMap(GameMatch $match): Collection
    {
        $individual = $match->participant_type === ParticipantType::Individual;

        return DB::table($individual ? 'tournament_players' : 'tournament_teams')
            ->where('tournament_id', $match->tournament_id)
            ->pluck('seed', $individual ? 'player_id' : 'team_id');
    }

    private function mainPairs(Collection $winners, Collection $selectedLosers): array
    {
        $availableWinners = $winners->values();
        $pairs = [];

        foreach ($selectedLosers as $loser) {
            $winnerIndex = $availableWinners->search(
                fn (array $winner): bool => $winner['participant_id'] !== $loser['lost_to'],
            );
            $winnerIndex = $winnerIndex === false ? 0 : $winnerIndex;
            $winner = $availableWinners->pull($winnerIndex);
            $availableWinners = $availableWinners->values();
            $pairs[] = [$winner['participant_id'], $loser['participant_id']];
        }

        foreach ($availableWinners->chunk(2) as $pair) {
            $pairs[] = [$pair->first()['participant_id'], $pair->last()['participant_id']];
        }

        return $pairs;
    }
}
