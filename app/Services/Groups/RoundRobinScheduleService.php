<?php

namespace App\Services\Groups;

final class RoundRobinScheduleService
{
    /** @return array<int, list<array{0: int, 1: int}>> */
    public function generate(array $participantIds): array
    {
        $rotation = array_values($participantIds);
        if (count($rotation) % 2 !== 0) {
            $rotation[] = null;
        }

        $rounds = [];
        $participantCount = count($rotation);
        for ($round = 0; $round < $participantCount - 1; $round++) {
            $pairs = [];
            for ($index = 0; $index < $participantCount / 2; $index++) {
                $first = $rotation[$index];
                $second = $rotation[$participantCount - 1 - $index];
                if ($first !== null && $second !== null) {
                    $pairs[] = ($round + $index) % 2 === 0 ? [$first, $second] : [$second, $first];
                }
            }
            $rounds[$round + 1] = $pairs;
            $rotation = [$rotation[0], $rotation[$participantCount - 1], ...array_slice($rotation, 1, $participantCount - 2)];
        }

        return $rounds;
    }
}
