<?php

namespace App\Services\Draw;

use App\Enums\TournamentFormat;
use App\Models\Tournament;
use App\Repositories\Contracts\TournamentDrawRepositoryInterface;

final class RematchAwarePairingService
{
    public function __construct(private readonly TournamentDrawRepositoryInterface $draws) {}

    public function pair(Tournament $tournament, array $orderedIds, bool $avoidRematches, bool $manualPairing = false): array
    {
        if ($tournament->format === TournamentFormat::SingleElimination) {
            return $this->qualifyingSingleElimination($tournament, $orderedIds, $avoidRematches, $manualPairing);
        }

        $bracketSize = $this->nextPowerOfTwo(count($orderedIds));
        $byeCount = $bracketSize - count($orderedIds);
        $remaining = array_values($orderedIds);
        $byes = array_splice($remaining, 0, $byeCount);
        $history = $avoidRematches
            ? $this->draws->encounterCounts($tournament, $tournament->participant_type, $orderedIds)
            : [];
        $pairs = array_map(fn (int $participantId): array => [$participantId, null], $byes);

        while ($remaining !== []) {
            $first = array_shift($remaining);
            $opponentIndex = $this->opponentIndex($first, $remaining, $history, $avoidRematches);
            $second = $remaining[$opponentIndex];
            array_splice($remaining, $opponentIndex, 1);
            $pairs[] = [$first, $second];
        }

        return ['bracket_size' => $bracketSize, 'bye_count' => $byeCount, 'pairs' => $pairs];
    }

    private function qualifyingSingleElimination(Tournament $tournament, array $orderedIds, bool $avoidRematches, bool $manualPairing): array
    {
        $participantCount = count($orderedIds);
        $qualifyingMatchCount = intdiv($participantCount, 2);
        $bracketSize = $this->nextPowerOfTwo($qualifyingMatchCount);
        $bestLoserCount = $bracketSize - $qualifyingMatchCount;
        $history = $avoidRematches
            ? $this->draws->encounterCounts($tournament, $tournament->participant_type, $orderedIds)
            : [];
        $qualifyingPairs = $manualPairing
            ? array_map(fn (array $pair): array => array_values($pair), array_chunk($orderedIds, 2))
            : $this->pairIds($orderedIds, $history, $avoidRematches);
        $mainMatches = array_fill(0, intdiv($bracketSize, 2), ['a' => [], 'b' => []]);

        return [
            'bracket_size' => $bracketSize,
            'bye_count' => 0,
            'preliminary_count' => $qualifyingMatchCount,
            'best_loser_count' => $bestLoserCount,
            'repechage' => true,
            'pairs' => $qualifyingPairs,
            'preliminary_pairs' => $qualifyingPairs,
            'main_matches' => $mainMatches,
            'direct_participant_ids' => [],
        ];
    }

    private function pairIds(array $participantIds, array $history, bool $avoidRematches): array
    {
        $remaining = array_values($participantIds);
        $pairs = [];

        while ($remaining !== []) {
            $first = array_shift($remaining);
            $opponentIndex = $this->opponentIndex($first, $remaining, $history, $avoidRematches);
            $second = $remaining[$opponentIndex];
            array_splice($remaining, $opponentIndex, 1);
            $pairs[] = [$first, $second];
        }

        return $pairs;
    }

    private function opponentIndex(int $first, array $candidates, array $history, bool $avoidRematches): int
    {
        $expectedIndex = count($candidates) - 1;

        if (! $avoidRematches) {
            return $expectedIndex;
        }

        $bestIndex = $expectedIndex;
        $bestScore = PHP_INT_MAX;

        foreach ($candidates as $index => $candidate) {
            $encounters = $history[$this->pairKey($first, $candidate)] ?? 0;
            $score = ($encounters * 1000) + abs($expectedIndex - $index);

            if ($score < $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        return $bestIndex;
    }

    private function nextPowerOfTwo(int $number): int
    {
        $power = 1;

        while ($power < $number) {
            $power *= 2;
        }

        return $power;
    }

    private function pairKey(int $first, int $second): string
    {
        return min($first, $second).':'.max($first, $second);
    }
}
