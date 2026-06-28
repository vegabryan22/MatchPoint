<?php

namespace App\Services\Brackets;

use App\Enums\BracketType;
use App\Enums\MatchSlot;

final class BracketBlueprint
{
    /** @var array<string, array<string, mixed>> */
    private array $rounds = [];

    /** @var array<string, array<string, mixed>> */
    private array $matches = [];

    public function addRound(string $key, string $name, int $number, BracketType $bracket): void
    {
        $this->rounds[$key] = compact('name', 'number', 'bracket');
    }

    public function addMatch(string $key, string $roundKey, int $sequence, array $attributes = []): void
    {
        $this->matches[$key] = [
            'round_key' => $roundKey,
            'sequence' => $sequence,
            'attributes' => $attributes,
            'winner_target' => null,
            'loser_target' => null,
        ];
    }

    public function linkWinner(string $source, string $target, MatchSlot $slot): void
    {
        $this->matches[$source]['winner_target'] = ['match' => $target, 'slot' => $slot];
    }

    public function linkLoser(string $source, string $target, MatchSlot $slot): void
    {
        $this->matches[$source]['loser_target'] = ['match' => $target, 'slot' => $slot];
    }

    /** @return array<string, array<string, mixed>> */
    public function rounds(): array
    {
        return $this->rounds;
    }

    /** @return array<string, array<string, mixed>> */
    public function matches(): array
    {
        return $this->matches;
    }
}
