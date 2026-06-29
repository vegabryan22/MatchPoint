<?php

namespace App\Enums;

enum PlayStationController: string
{
    case Ps4 = 'ps4';
    case Ps5 = 'ps5';

    public function label(): string
    {
        return match ($this) {
            self::Ps4 => 'Control PS4',
            self::Ps5 => 'Control PS5',
        };
    }
}
