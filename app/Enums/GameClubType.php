<?php

namespace App\Enums;

enum GameClubType: string
{
    case Club = 'club';
    case NationalTeam = 'national_team';

    public function label(): string
    {
        return match ($this) {
            self::Club => 'Club',
            self::NationalTeam => 'Selección nacional',
        };
    }
}
