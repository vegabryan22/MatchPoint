<?php

namespace App\Services;

use App\Enums\ParticipantType;
use App\Models\GameMatch;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class StatisticsService
{
    public function __construct(private readonly StatisticsRepositoryInterface $statistics) {}

    public function ranking(array $filters): array
    {
        $type = ParticipantType::tryFrom($filters['participant_type'] ?? '') ?? ParticipantType::Individual;
        $filters['participant_type'] = $type;
        $matches = $this->statistics->completedMatches($filters);
        $participantIds = $matches
            ->flatMap(fn (GameMatch $match): array => [$match->participant_a_id, $match->participant_b_id])
            ->filter()
            ->unique()
            ->values()
            ->all();
        $participants = $this->statistics->participants($type, $participantIds);
        $rows = [];

        foreach ($matches as $match) {
            $this->accumulate($rows, $match, $match->participant_a_id, true);
            $this->accumulate($rows, $match, $match->participant_b_id, false);
        }

        $ranking = collect($rows)
            ->map(function (array $row, int $participantId) use ($participants): array {
                $participant = $participants->get($participantId);
                $row['participant'] = $participant;
                $row['name'] = $participant === null
                    ? 'Participante eliminado'
                    : ($participant->nickname ?? $participant->name);
                $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];
                $row['average'] = $row['played'] === 0 ? 0.0 : round($row['goals_for'] / $row['played'], 2);
                $row['win_rate'] = $row['played'] === 0 ? 0.0 : round(($row['wins'] / $row['played']) * 100, 1);
                $row['streak'] = $row['streak_result'] === null
                    ? '—'
                    : $row['streak_count'].match ($row['streak_result']) {
                        'win' => 'V',
                        'draw' => 'E',
                        default => 'D',
                    };

                return $row;
            })
            ->sort(function (array $first, array $second): int {
                return [$second['wins'], $second['goal_difference'], $second['goals_for'], $first['name']]
                    <=> [$first['wins'], $first['goal_difference'], $first['goals_for'], $second['name']];
            })
            ->values();

        $this->assignRanks($ranking);

        return [
            'ranking' => $ranking,
            'participantType' => $type,
            'tournaments' => $this->statistics->tournaments(),
            'filters' => $filters,
        ];
    }

    public function participant(ParticipantType $type, int $participantId, array $filters): array
    {
        $filters['participant_type'] = $type->value;
        $rankingData = $this->ranking($filters);
        $row = $rankingData['ranking']->firstWhere('participant_id', $participantId);
        $participant = $this->statistics->participants($type, [$participantId])->get($participantId);

        if ($participant === null) {
            throw ValidationException::withMessages(['participant' => 'El participante solicitado no existe.']);
        }

        $matches = $this->statistics->completedMatches([
            ...$filters,
            'participant_type' => $type,
        ])->filter(fn (GameMatch $match): bool => in_array($participantId, [$match->participant_a_id, $match->participant_b_id], true));
        $opponentIds = $matches->map(fn (GameMatch $match): int => $match->participant_a_id === $participantId
            ? $match->participant_b_id
            : $match->participant_a_id)->unique()->values()->all();
        $opponents = $this->statistics->participants($type, $opponentIds);
        $history = $matches->reverse()->values()->map(function (GameMatch $match) use ($participantId, $opponents): array {
            $isParticipantA = $match->participant_a_id === $participantId;
            $opponentId = $isParticipantA ? $match->participant_b_id : $match->participant_a_id;
            $opponent = $opponents->get($opponentId);

            return [
                'match' => $match,
                'opponent' => $opponent,
                'opponent_name' => $opponent?->nickname ?? $opponent?->name ?? 'Participante eliminado',
                'result' => $match->winner_id === null ? 'Empate' : ($match->winner_id === $participantId ? 'Victoria' : 'Derrota'),
                'goals_for' => $match->scores->sum($isParticipantA ? 'participant_a_score' : 'participant_b_score'),
                'goals_against' => $match->scores->sum($isParticipantA ? 'participant_b_score' : 'participant_a_score'),
            ];
        });

        return [
            ...$rankingData,
            'participant' => $participant,
            'statistics' => $row ?? $this->emptyRow($participantId, $participant),
            'history' => $history,
        ];
    }

    private function accumulate(array &$rows, GameMatch $match, int $participantId, bool $isParticipantA): void
    {
        $rows[$participantId] ??= $this->emptyRow($participantId);
        $draw = $match->winner_id === null;
        $won = $match->winner_id === $participantId;
        $result = $draw ? 'draw' : ($won ? 'win' : 'loss');
        $goalsFor = $match->scores->sum($isParticipantA ? 'participant_a_score' : 'participant_b_score');
        $goalsAgainst = $match->scores->sum($isParticipantA ? 'participant_b_score' : 'participant_a_score');

        $rows[$participantId]['played']++;
        $rows[$participantId][$draw ? 'draws' : ($won ? 'wins' : 'losses')]++;
        $rows[$participantId]['goals_for'] += $goalsFor;
        $rows[$participantId]['goals_against'] += $goalsAgainst;
        if ($rows[$participantId]['streak_result'] === $result) {
            $rows[$participantId]['streak_count']++;
        } else {
            $rows[$participantId]['streak_result'] = $result;
            $rows[$participantId]['streak_count'] = 1;
        }
    }

    private function emptyRow(int $participantId, mixed $participant = null): array
    {
        return [
            'rank' => null,
            'participant_id' => $participantId,
            'participant' => $participant,
            'name' => $participant?->nickname ?? $participant?->name ?? 'Participante',
            'played' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
            'average' => 0.0,
            'win_rate' => 0.0,
            'streak_result' => null,
            'streak_count' => 0,
            'streak' => '—',
        ];
    }

    private function assignRanks(Collection $ranking): void
    {
        $lastMetrics = null;
        $lastRank = 0;

        $ranking->transform(function (array $row, int $index) use (&$lastMetrics, &$lastRank): array {
            $metrics = [$row['wins'], $row['goal_difference'], $row['goals_for']];
            if ($metrics !== $lastMetrics) {
                $lastRank = $index + 1;
                $lastMetrics = $metrics;
            }
            $row['rank'] = $lastRank;

            return $row;
        });
    }
}
