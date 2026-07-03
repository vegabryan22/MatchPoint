<?php

namespace App\Services\Draw;

use App\Enums\TournamentFormat;
use App\Models\Tournament;
use App\Repositories\Contracts\TournamentDrawRepositoryInterface;

final class RematchAwarePairingService
{
    public function __construct(private readonly TournamentDrawRepositoryInterface $draws) {}

    public function pair(Tournament $tournament, array $orderedIds, bool $avoidRematches): array
    {
        if ($tournament->format !== TournamentFormat::DoubleElimination) {
            return $this->compactSingleElimination($tournament, $orderedIds, $avoidRematches);
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

    private function compactSingleElimination(Tournament $tournament, array $orderedIds, bool $avoidRematches): array
    {
        $participantCount = count($orderedIds);
        $bracketSize = $this->previousPowerOfTwo($participantCount);
        $preliminaryCount = $participantCount - $bracketSize;
        $directCount = $preliminaryCount > 0 ? $participantCount - ($preliminaryCount * 2) : $participantCount;
        $directParticipantIds = array_slice($orderedIds, 0, $directCount);
        $preliminaryParticipantIds = array_slice($orderedIds, $directCount);
        $history = $avoidRematches
            ? $this->draws->encounterCounts($tournament, $tournament->participant_type, $orderedIds)
            : [];
        $preliminaryPairs = $this->pairIds($preliminaryParticipantIds, $history, $avoidRematches);
        $mainDirectPairs = $preliminaryCount === 0 ? $this->pairIds($directParticipantIds, $history, $avoidRematches) : [];
        $mainMatches = $this->mainMatches($directParticipantIds, $preliminaryCount, $history, $avoidRematches);

        return [
            'bracket_size' => $bracketSize,
            'bye_count' => $preliminaryCount > 0 ? $directCount : 0,
            'preliminary_count' => $preliminaryCount,
            'pairs' => $preliminaryCount > 0 ? $preliminaryPairs : $mainDirectPairs,
            'preliminary_pairs' => $preliminaryPairs,
            'main_matches' => $mainMatches,
            'direct_participant_ids' => $directParticipantIds,
        ];
    }

    private function mainMatches(array $directIds, int $preliminaryCount, array $history, bool $avoidRematches): array
    {
        if ($preliminaryCount === 0) {
            return array_map(fn (array $pair): array => [
                'a' => ['participant_id' => $pair[0]],
                'b' => ['participant_id' => $pair[1]],
            ], $this->pairIds($directIds, $history, $avoidRematches));
        }

        $matches = [];
        $crossMatches = min(count($directIds), $preliminaryCount);
        for ($index = 0; $index < $crossMatches; $index++) {
            $matches[] = [
                'a' => ['participant_id' => $directIds[$index]],
                'b' => ['preliminary_match' => $index + 1],
            ];
        }

        $remainingDirect = array_slice($directIds, $crossMatches);
        foreach ($this->pairIds($remainingDirect, $history, $avoidRematches) as $pair) {
            $matches[] = [
                'a' => ['participant_id' => $pair[0]],
                'b' => ['participant_id' => $pair[1]],
            ];
        }

        $remainingPreliminary = range($crossMatches + 1, $preliminaryCount);
        if ($crossMatches === $preliminaryCount) {
            $remainingPreliminary = [];
        }
        foreach (array_chunk($remainingPreliminary, 2) as $pair) {
            $matches[] = [
                'a' => ['preliminary_match' => $pair[0]],
                'b' => ['preliminary_match' => $pair[1]],
            ];
        }

        return $matches;
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

    private function previousPowerOfTwo(int $number): int
    {
        $power = 1;

        while (($power * 2) <= $number) {
            $power *= 2;
        }

        return $power;
    }

    private function pairKey(int $first, int $second): string
    {
        return min($first, $second).':'.max($first, $second);
    }
}
