<?php

namespace App\Services\Groups;

use App\Enums\MatchStatus;
use App\Models\TournamentGroup;
use Illuminate\Support\Collection;

final class StandingsService
{
    public function calculate(TournamentGroup $group, Collection $participants): Collection
    {
        $rows = $group->participants->mapWithKeys(fn ($entry): array => [$entry->participant_id => [
            'participant_id' => $entry->participant_id,
            'participant' => $participants->get($entry->participant_id),
            'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0,
            'goals_for' => 0, 'goals_against' => 0, 'points' => 0,
        ]])->all();

        foreach ($group->matches->where('status', MatchStatus::Completed) as $match) {
            $goalsA = $match->scores->sum('participant_a_score');
            $goalsB = $match->scores->sum('participant_b_score');
            foreach ([[$match->participant_a_id, $goalsA, $goalsB], [$match->participant_b_id, $goalsB, $goalsA]] as [$id, $for, $against]) {
                $rows[$id]['played']++;
                $rows[$id]['goals_for'] += $for;
                $rows[$id]['goals_against'] += $against;
                if ($match->winner_id === null) {
                    $rows[$id]['draws']++;
                    $rows[$id]['points']++;
                } elseif ($match->winner_id === $id) {
                    $rows[$id]['wins']++;
                    $rows[$id]['points'] += 3;
                } else {
                    $rows[$id]['losses']++;
                }
            }
        }

        return collect($rows)->map(function (array $row): array {
            $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];
            $row['name'] = $row['participant']?->nickname ?? $row['participant']?->name ?? 'Participante';

            return $row;
        })->sort(fn (array $a, array $b): int => [$b['points'], $b['goal_difference'], $b['goals_for'], $a['name']] <=> [$a['points'], $a['goal_difference'], $a['goals_for'], $b['name']])
            ->values()->map(function (array $row, int $index): array {
                $row['position'] = $index + 1;

                return $row;
            });
    }
}
