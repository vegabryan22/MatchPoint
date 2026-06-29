<?php

namespace App\Enums;

enum AcademicLevel: string
{
    case Seventh = '7';
    case Eighth = '8';
    case Ninth = '9';
    case Tenth = '10';
    case Eleventh = '11';
    case Twelfth = '12';

    public function label(): string
    {
        return match ($this) {
            self::Seventh => 'Sétimo 7',
            self::Eighth => 'Octavo 8',
            self::Ninth => 'Noveno 9',
            self::Tenth => 'Décimo 10',
            self::Eleventh => 'Undécimo 11',
            self::Twelfth => 'Duodécimo 12',
        };
    }
}
