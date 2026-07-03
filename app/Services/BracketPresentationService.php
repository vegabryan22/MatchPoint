<?php

namespace App\Services;

use App\Enums\BracketType;
use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Collection;

final class BracketPresentationService
{
    public function present(Tournament $tournament, Collection $participants, Collection $clubs): array
    {
        $sections = collect(BracketType::cases())
            ->reject(fn (BracketType $type): bool => $type === BracketType::Group)
            ->map(function (BracketType $type) use ($tournament, $participants, $clubs): array {
                $rounds = $tournament->rounds->where('bracket', $type)->sortBy('number')->values();

                $presentedRounds = $rounds->map(fn ($round, int $roundIndex): array => [
                    'model' => $round,
                    'name' => $round->name,
                    'is_last' => $roundIndex === $rounds->count() - 1,
                    'matches' => $round->matches
                        ->sortBy('sequence')
                        ->map(fn ($match): array => $this->match($match, $tournament, $participants, $clubs))
                        ->values()
                        ->all(),
                ])->all();

                $symmetricLayout = $type === BracketType::Main
                    ? $this->symmetricLayout($presentedRounds)
                    : null;

                return [
                    'type' => $type,
                    'label' => $type->label(),
                    'match_count' => $rounds->sum(fn ($round): int => $round->matches->count()),
                    'rounds' => $presentedRounds,
                    'layout' => $symmetricLayout === null ? 'linear' : 'symmetric',
                    'left_rounds' => $symmetricLayout['left_rounds'] ?? [],
                    'center_round' => $symmetricLayout['center_round'] ?? null,
                    'center_match' => $symmetricLayout['center_match'] ?? null,
                    'right_rounds' => $symmetricLayout['right_rounds'] ?? [],
                ];
            })->filter(fn (array $section): bool => $section['rounds'] !== [])->values()->all();

        $champion = $tournament->champion;
        $championParticipant = $champion === null ? null : $participants->get($champion->participant_id);

        return [
            'bracketSections' => $sections,
            'bracketChampion' => $championParticipant === null ? null : [
                'name' => $this->participantName($championParticipant, $tournament->participant_type),
                'crowned_at' => $champion->crowned_at,
            ],
        ];
    }

    private function symmetricLayout(array $rounds): ?array
    {
        if ($rounds === []) {
            return null;
        }

        $centerRound = array_pop($rounds);
        $centerMatch = $centerRound['matches'][0] ?? null;

        if ($centerMatch === null) {
            return null;
        }

        $leftRounds = [];
        $rightRounds = [];

        foreach ($rounds as $round) {
            $half = (int) ceil(count($round['matches']) / 2);
            $leftRounds[] = [...$round, 'matches' => array_slice($round['matches'], 0, $half)];
            $rightRounds[] = [...$round, 'matches' => array_slice($round['matches'], $half)];
        }

        return [
            'left_rounds' => $leftRounds,
            'center_round' => $centerRound,
            'center_match' => $centerMatch,
            'right_rounds' => array_reverse($rightRounds),
        ];
    }

    private function match($match, Tournament $tournament, Collection $participants, Collection $clubs): array
    {
        $participantA = $participants->get($match->participant_a_id);
        $participantB = $participants->get($match->participant_b_id);
        $clubA = $clubs->get($participantA?->pivot?->game_club_id);
        $clubB = $clubs->get($participantB?->pivot?->game_club_id);
        $hasScore = $match->scores->isNotEmpty();

        return [
            'model' => $match,
            'participant_a' => $participantA === null ? 'Por definir' : $this->participantName($participantA, $tournament->participant_type),
            'participant_a_real_name' => $this->participantRealName($participantA, $tournament->participant_type),
            'participant_b' => $participantB === null
                ? ($match->status === MatchStatus::Bye ? 'Pase automático' : 'Por definir')
                : $this->participantName($participantB, $tournament->participant_type),
            'participant_b_real_name' => $this->participantRealName($participantB, $tournament->participant_type),
            'score_a' => $hasScore ? $match->scores->sum('participant_a_score') : null,
            'score_b' => $hasScore ? $match->scores->sum('participant_b_score') : null,
            'penalties_a' => $hasScore ? $match->scores->sum('participant_a_penalties') : null,
            'penalties_b' => $hasScore ? $match->scores->sum('participant_b_penalties') : null,
            'has_penalties' => $match->scores->contains(fn ($score): bool => $score->participant_a_penalties !== null),
            'club_a' => $clubA === null ? null : ['name' => $clubA->name, 'crest' => $clubA->crestUrl(), 'flag' => $clubA->countryFlag()],
            'club_b' => $clubB === null ? null : ['name' => $clubB->name, 'crest' => $clubB->crestUrl(), 'flag' => $clubB->countryFlag()],
            'status_class' => match ($match->status) {
                MatchStatus::Completed => 'is-completed',
                MatchStatus::Bye => 'is-bye',
                MatchStatus::Cancelled => 'is-cancelled',
                default => 'is-pending',
            },
        ];
    }

    private function participantName(mixed $participant, ParticipantType $type): string
    {
        return $type === ParticipantType::Individual ? $participant->nickname : $participant->name;
    }

    private function participantRealName(mixed $participant, ParticipantType $type): ?string
    {
        if ($participant === null || $type !== ParticipantType::Individual) {
            return null;
        }

        return $participant->name;
    }
}
