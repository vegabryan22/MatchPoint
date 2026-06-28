<?php

namespace App\Enums;

enum ParticipantType: string
{
    case Individual = 'individual';
    case Team = 'team';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::Team => 'Equipos',
        };
    }
}
