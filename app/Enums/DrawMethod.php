<?php

namespace App\Enums;

enum DrawMethod: string
{
    case Random = 'random';
    case Automatic = 'automatic';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Random => 'Aleatorio',
            self::Automatic => 'Sembrado automático',
            self::Manual => 'Semillas manuales',
        };
    }
}
