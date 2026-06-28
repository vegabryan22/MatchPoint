<?php

namespace App\Enums;

/**
 * Dispositivos de control admitidos en el perfil competitivo.
 */
enum ControllerType: string
{
    case PlayStation = 'playstation';
    case Xbox = 'xbox';
    case Keyboard = 'keyboard';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PlayStation => 'PlayStation',
            self::Xbox => 'Xbox',
            self::Keyboard => 'Teclado',
            self::Other => 'Otro',
        };
    }
}
