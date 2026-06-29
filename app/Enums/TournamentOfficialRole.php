<?php

namespace App\Enums;

enum TournamentOfficialRole: string
{
    case Referee = 'referee';

    public function label(): string
    {
        return match ($this) {
            self::Referee => 'Árbitro',
        };
    }
}
