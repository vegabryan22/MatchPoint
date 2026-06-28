<?php

namespace App\Enums;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case Registration = 'registration';
    case InProgress = 'in_progress';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Registration => 'Inscripciones',
            self::InProgress => 'En curso',
            self::Finished => 'Finalizado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'text-bg-secondary',
            self::Registration => 'text-bg-info',
            self::InProgress => 'text-bg-warning',
            self::Finished => 'text-bg-success',
            self::Cancelled => 'text-bg-danger',
        };
    }
}
