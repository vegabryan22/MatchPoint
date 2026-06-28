<?php

namespace App\Enums;

enum RegistrationSource: string
{
    case Manual = 'manual';
    case Csv = 'csv';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Csv => 'CSV',
        };
    }
}
