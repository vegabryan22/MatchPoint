<?php

namespace App\Services\Draw;

use App\Contracts\SeedingStrategyInterface;
use App\Enums\DrawMethod;
use App\Enums\ParticipantType;
use App\Enums\PlayerLevel;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Collection;

final class AutomaticSeedingStrategy implements SeedingStrategyInterface
{
    public function method(): DrawMethod
    {
        return DrawMethod::Automatic;
    }

    public function order(Tournament $tournament, Collection $participants, array $data): array
    {
        return $participants
            ->sort(function (Player|Team $left, Player|Team $right) use ($tournament): int {
                $score = $this->score($right, $tournament) <=> $this->score($left, $tournament);

                return $score !== 0 ? $score : strcasecmp($this->name($left), $this->name($right));
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    private function score(Player|Team $participant, Tournament $tournament): float
    {
        if ($tournament->participant_type === ParticipantType::Individual) {
            return $this->levelScore($participant->level);
        }

        if ($participant->players->isEmpty()) {
            return 0;
        }

        return $participant->players->average(fn (Player $player): int => $this->levelScore($player->level));
    }

    private function levelScore(PlayerLevel $level): int
    {
        return match ($level) {
            PlayerLevel::Professional => 4,
            PlayerLevel::Advanced => 3,
            PlayerLevel::Intermediate => 2,
            PlayerLevel::Beginner => 1,
        };
    }

    private function name(Player|Team $participant): string
    {
        return $participant instanceof Player ? $participant->nickname : $participant->name;
    }
}
