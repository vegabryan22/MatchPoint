<?php

namespace App\Services\Brackets;

use App\Enums\BracketType;
use App\Enums\MatchSlot;
use App\Enums\TournamentFormat;
use App\Models\Tournament;

final class SingleEliminationBracketGenerator implements BracketGeneratorInterface
{
    public function supports(TournamentFormat $format): bool
    {
        return in_array($format, [TournamentFormat::SingleElimination, TournamentFormat::GroupsKnockout, TournamentFormat::WorldCup48], true);
    }

    public function build(Tournament $tournament, array $plan): BracketBlueprint
    {
        $blueprint = new BracketBlueprint;
        $preliminaryPairs = $plan['preliminary_pairs'] ?? [];
        $mainMatches = $plan['main_matches'] ?? $this->legacyMainMatches($plan['pairs']);
        $hasPreliminaryRound = $preliminaryPairs !== [];
        $roundCount = (int) log($plan['bracket_size'], 2);

        if ($hasPreliminaryRound) {
            $blueprint->addRound('p1', 'Ronda clasificatoria', 1, BracketType::Main);
            foreach ($preliminaryPairs as $index => $pair) {
                $sequence = $index + 1;
                $blueprint->addMatch('p1m'.$sequence, 'p1', $sequence, [
                    'participant_a_id' => $pair['participant_a_id'],
                    'participant_b_id' => $pair['participant_b_id'],
                ]);
            }
        }

        for ($roundNumber = 1; $roundNumber <= $roundCount; $roundNumber++) {
            $roundKey = $this->roundKey($roundNumber);
            $blueprint->addRound(
                $roundKey,
                $this->roundName($roundNumber, $roundCount),
                $roundNumber + ($hasPreliminaryRound ? 1 : 0),
                BracketType::Main,
            );

            $matchCount = intdiv($plan['bracket_size'], 2 ** $roundNumber);
            for ($sequence = 1; $sequence <= $matchCount; $sequence++) {
                $matchKey = $this->matchKey($roundNumber, $sequence);
                $blueprint->addMatch(
                    $matchKey,
                    $roundKey,
                    $sequence,
                    $roundNumber === 1 ? $this->mainFirstRoundAttributes($mainMatches[$sequence - 1]) : [],
                );

                if ($roundNumber === 1) {
                    $this->linkPreliminarySources($blueprint, $mainMatches[$sequence - 1], $matchKey);
                } elseif ($roundNumber > 1) {
                    $firstSource = ($sequence * 2) - 1;
                    $blueprint->linkWinner($this->matchKey($roundNumber - 1, $firstSource), $matchKey, MatchSlot::A);
                    $blueprint->linkWinner($this->matchKey($roundNumber - 1, $firstSource + 1), $matchKey, MatchSlot::B);
                }
            }
        }

        return $blueprint;
    }

    private function mainFirstRoundAttributes(array $definition): array
    {
        return [
            'participant_a_id' => $definition['a']['participant_id'] ?? null,
            'participant_b_id' => $definition['b']['participant_id'] ?? null,
        ];
    }

    private function linkPreliminarySources(BracketBlueprint $blueprint, array $definition, string $target): void
    {
        foreach (['a' => MatchSlot::A, 'b' => MatchSlot::B] as $key => $slot) {
            if (isset($definition[$key]['preliminary_match'])) {
                $blueprint->linkWinner('p1m'.$definition[$key]['preliminary_match'], $target, $slot);
            }
        }
    }

    private function legacyMainMatches(array $pairs): array
    {
        return array_map(fn (array $pair): array => [
            'a' => ['participant_id' => $pair['participant_a_id']],
            'b' => ['participant_id' => $pair['participant_b_id']],
        ], $pairs);
    }

    private function roundName(int $number, int $total): string
    {
        return match ($total - $number) {
            0 => 'Final',
            1 => 'Semifinales',
            2 => 'Cuartos de final',
            3 => 'Octavos de final',
            4 => 'Dieciseisavos de final',
            5 => 'Treintaidosavos de final',
            default => 'Ronda '.$number,
        };
    }

    private function roundKey(int $round): string
    {
        return 'w'.$round;
    }

    private function matchKey(int $round, int $sequence): string
    {
        return $this->roundKey($round).'m'.$sequence;
    }
}
