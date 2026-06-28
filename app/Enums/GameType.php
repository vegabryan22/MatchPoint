<?php

namespace App\Enums;

enum GameType: string
{
    case EaSportsFc = 'ea_sports_fc';
    case Fifa = 'fifa';
    case Pes = 'pes';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EaSportsFc => 'EA Sports FC',
            self::Fifa => 'FIFA',
            self::Pes => 'PES',
            self::Other => 'Otro',
        };
    }
}
