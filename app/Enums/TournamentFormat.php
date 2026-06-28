<?php

namespace App\Enums;

enum TournamentFormat: string
{
    case SingleElimination = 'single_elimination';
    case DoubleElimination = 'double_elimination';
    case RoundRobin = 'round_robin';
    case GroupsKnockout = 'groups_knockout';
    case League = 'league';

    public function label(): string
    {
        return match ($this) {
            self::SingleElimination => 'Eliminación simple',
            self::DoubleElimination => 'Eliminación doble',
            self::RoundRobin => 'Round Robin',
            self::GroupsKnockout => 'Fase de grupos + eliminación',
            self::League => 'Liga',
        };
    }
}
