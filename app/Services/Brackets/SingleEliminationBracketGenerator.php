<?php

namespace App\Services\Brackets;

use App\Enums\BracketType;
use App\Enums\MatchSlot;
use App\Enums\MatchStatus;
use App\Enums\TournamentFormat;
use App\Models\Tournament;

final class SingleEliminationBracketGenerator implements BracketGeneratorInterface
{
    public function supports(TournamentFormat $format): bool
    {
        return in_array($format, [TournamentFormat::SingleElimination, TournamentFormat::GroupsKnockout], true);
    }

    public function build(Tournament $tournament, array $plan): BracketBlueprint
    {
        $blueprint = new BracketBlueprint;
        $roundCount = (int) log($plan['bracket_size'], 2);

        for ($roundNumber = 1; $roundNumber <= $roundCount; $roundNumber++) {
            $roundKey = $this->roundKey($roundNumber);
            $blueprint->addRound(
                $roundKey,
                $this->roundName($roundNumber, $roundCount),
                $roundNumber,
                BracketType::Main,
            );

            $matchCount = intdiv($plan['bracket_size'], 2 ** $roundNumber);
            for ($sequence = 1; $sequence <= $matchCount; $sequence++) {
                $matchKey = $this->matchKey($roundNumber, $sequence);
                $blueprint->addMatch(
                    $matchKey,
                    $roundKey,
                    $sequence,
                    $roundNumber === 1 ? $this->firstRoundAttributes($plan['pairs'][$sequence - 1]) : [],
                );

                if ($roundNumber > 1) {
                    $firstSource = ($sequence * 2) - 1;
                    $blueprint->linkWinner($this->matchKey($roundNumber - 1, $firstSource), $matchKey, MatchSlot::A);
                    $blueprint->linkWinner($this->matchKey($roundNumber - 1, $firstSource + 1), $matchKey, MatchSlot::B);
                }
            }
        }

        return $blueprint;
    }

    private function firstRoundAttributes(array $pair): array
    {
        $isBye = $pair['participant_b_id'] === null;

        return [
            'participant_a_id' => $pair['participant_a_id'],
            'participant_b_id' => $pair['participant_b_id'],
            'winner_id' => $isBye ? $pair['participant_a_id'] : null,
            'status' => $isBye ? MatchStatus::Bye : MatchStatus::Pending,
        ];
    }

    private function roundName(int $number, int $total): string
    {
        return match ($total - $number) {
            0 => 'Final',
            1 => 'Semifinales',
            2 => 'Cuartos de final',
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
