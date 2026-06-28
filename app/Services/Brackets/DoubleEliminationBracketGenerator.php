<?php

namespace App\Services\Brackets;

use App\Enums\BracketType;
use App\Enums\MatchSlot;
use App\Enums\MatchStatus;
use App\Enums\TournamentFormat;
use App\Models\Tournament;

final class DoubleEliminationBracketGenerator implements BracketGeneratorInterface
{
    public function supports(TournamentFormat $format): bool
    {
        return $format === TournamentFormat::DoubleElimination;
    }

    public function build(Tournament $tournament, array $plan): BracketBlueprint
    {
        $blueprint = new BracketBlueprint;
        $winnerRoundCount = (int) log($plan['bracket_size'], 2);

        $this->buildWinnerBracket($blueprint, $plan, $winnerRoundCount);
        $this->buildFinals($blueprint);

        if ($winnerRoundCount === 1) {
            $blueprint->linkWinner('w1m1', 'f1m1', MatchSlot::A);
            $blueprint->linkLoser('w1m1', 'f1m1', MatchSlot::B);

            return $blueprint;
        }

        $this->buildLoserBracket($blueprint, $plan['bracket_size'], $winnerRoundCount);
        $blueprint->linkWinner($this->winnerMatchKey($winnerRoundCount, 1), 'f1m1', MatchSlot::A);
        $blueprint->linkWinner($this->loserMatchKey(($winnerRoundCount * 2) - 2, 1), 'f1m1', MatchSlot::B);

        return $blueprint;
    }

    private function buildWinnerBracket(BracketBlueprint $blueprint, array $plan, int $roundCount): void
    {
        for ($round = 1; $round <= $roundCount; $round++) {
            $roundKey = 'w'.$round;
            $blueprint->addRound($roundKey, 'Ganadores · '.$this->roundName($round, $roundCount), $round, BracketType::Main);
            $matchCount = intdiv($plan['bracket_size'], 2 ** $round);

            for ($sequence = 1; $sequence <= $matchCount; $sequence++) {
                $matchKey = $this->winnerMatchKey($round, $sequence);
                $attributes = $round === 1 ? $this->firstRoundAttributes($plan['pairs'][$sequence - 1]) : [];
                $blueprint->addMatch($matchKey, $roundKey, $sequence, $attributes);

                if ($round > 1) {
                    $source = ($sequence * 2) - 1;
                    $blueprint->linkWinner($this->winnerMatchKey($round - 1, $source), $matchKey, MatchSlot::A);
                    $blueprint->linkWinner($this->winnerMatchKey($round - 1, $source + 1), $matchKey, MatchSlot::B);
                }
            }
        }
    }

    private function buildLoserBracket(BracketBlueprint $blueprint, int $bracketSize, int $winnerRoundCount): void
    {
        $loserRoundCount = ($winnerRoundCount * 2) - 2;

        for ($round = 1; $round <= $loserRoundCount; $round++) {
            $stage = intdiv($round + 1, 2);
            $matchCount = intdiv($bracketSize, 2 ** ($stage + 1));
            $roundKey = 'l'.$round;
            $blueprint->addRound($roundKey, 'Perdedores · Ronda '.$round, $round, BracketType::Losers);

            for ($sequence = 1; $sequence <= $matchCount; $sequence++) {
                $blueprint->addMatch($this->loserMatchKey($round, $sequence), $roundKey, $sequence);
            }
        }

        $firstRoundMatches = intdiv($bracketSize, 2);
        for ($sequence = 1; $sequence <= $firstRoundMatches; $sequence++) {
            $targetSequence = (int) ceil($sequence / 2);
            $slot = $sequence % 2 === 1 ? MatchSlot::A : MatchSlot::B;
            $blueprint->linkLoser($this->winnerMatchKey(1, $sequence), $this->loserMatchKey(1, $targetSequence), $slot);
        }

        for ($winnerRound = 2; $winnerRound <= $winnerRoundCount; $winnerRound++) {
            $matchCount = intdiv($bracketSize, 2 ** $winnerRound);
            $targetRound = ($winnerRound * 2) - 2;
            for ($sequence = 1; $sequence <= $matchCount; $sequence++) {
                $blueprint->linkLoser(
                    $this->winnerMatchKey($winnerRound, $sequence),
                    $this->loserMatchKey($targetRound, $sequence),
                    MatchSlot::B,
                );
            }
        }

        for ($round = 1; $round < $loserRoundCount; $round++) {
            $matchCount = intdiv($bracketSize, 2 ** (intdiv($round + 1, 2) + 1));
            for ($sequence = 1; $sequence <= $matchCount; $sequence++) {
                if ($round % 2 === 1) {
                    $blueprint->linkWinner(
                        $this->loserMatchKey($round, $sequence),
                        $this->loserMatchKey($round + 1, $sequence),
                        MatchSlot::A,
                    );
                } else {
                    $targetSequence = (int) ceil($sequence / 2);
                    $slot = $sequence % 2 === 1 ? MatchSlot::A : MatchSlot::B;
                    $blueprint->linkWinner(
                        $this->loserMatchKey($round, $sequence),
                        $this->loserMatchKey($round + 1, $targetSequence),
                        $slot,
                    );
                }
            }
        }
    }

    private function buildFinals(BracketBlueprint $blueprint): void
    {
        $blueprint->addRound('f1', 'Gran final', 1, BracketType::Finals);
        $blueprint->addMatch('f1m1', 'f1', 1);
        $blueprint->addRound('f2', 'Final de reinicio', 2, BracketType::Finals);
        $blueprint->addMatch('f2m1', 'f2', 1, ['is_conditional' => true]);
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
            default => 'Ronda '.$number,
        };
    }

    private function winnerMatchKey(int $round, int $sequence): string
    {
        return 'w'.$round.'m'.$sequence;
    }

    private function loserMatchKey(int $round, int $sequence): string
    {
        return 'l'.$round.'m'.$sequence;
    }
}
