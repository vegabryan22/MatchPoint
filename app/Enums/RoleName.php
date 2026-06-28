<?php

namespace App\Enums;

/**
 * Identificadores estables de los roles funcionales de MatchPoint.
 */
enum RoleName: string
{
    case Administrator = 'administrator';
    case Organizer = 'organizer';
    case Referee = 'referee';
    case Player = 'player';
    case Guest = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrador',
            self::Organizer => 'Organizador',
            self::Referee => 'Árbitro',
            self::Player => 'Jugador',
            self::Guest => 'Invitado',
        };
    }
}
