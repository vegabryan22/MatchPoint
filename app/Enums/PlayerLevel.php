<?php

namespace App\Enums;

/**
 * Nivel competitivo declarado por un jugador.
 */
enum PlayerLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Professional = 'professional';

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Principiante',
            self::Intermediate => 'Intermedio',
            self::Advanced => 'Avanzado',
            self::Professional => 'Profesional',
        };
    }
}
