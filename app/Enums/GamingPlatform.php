<?php

namespace App\Enums;

enum GamingPlatform: string
{
    case PlayStation5 = 'ps5';
    case PlayStation4 = 'ps4';
    case XboxSeries = 'xbox_series';
    case XboxOne = 'xbox_one';
    case Pc = 'pc';
    case NintendoSwitch = 'nintendo_switch';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PlayStation5 => 'PlayStation 5',
            self::PlayStation4 => 'PlayStation 4',
            self::XboxSeries => 'Xbox Series',
            self::XboxOne => 'Xbox One',
            self::Pc => 'PC',
            self::NintendoSwitch => 'Nintendo Switch',
            self::Other => 'Otra',
        };
    }
}
