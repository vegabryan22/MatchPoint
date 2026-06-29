<?php

namespace App\Enums;

enum RegistrationSource: string
{
    case Manual = 'manual';
    case Csv = 'csv';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Csv => 'CSV',
            self::Public => 'Inscripción pública',
        };
    }
}
