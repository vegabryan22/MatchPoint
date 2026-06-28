<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Pending = 'pending';
    case Bye = 'bye';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Bye => 'Pase automático',
            self::Completed => 'Finalizado',
            self::Cancelled => 'Cancelado',
        };
    }
}
