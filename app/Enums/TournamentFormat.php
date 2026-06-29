<?php

namespace App\Enums;

enum TournamentFormat: string
{
    case SingleElimination = 'single_elimination';
    case DoubleElimination = 'double_elimination';
    case RoundRobin = 'round_robin';
    case GroupsKnockout = 'groups_knockout';
    case WorldCup48 = 'world_cup_48';
    case League = 'league';

    public function label(): string
    {
        return match ($this) {
            self::SingleElimination => 'Eliminación simple',
            self::DoubleElimination => 'Eliminación doble',
            self::RoundRobin => 'Round Robin',
            self::GroupsKnockout => 'Fase de grupos + eliminación',
            self::WorldCup48 => 'Mundial 48 · 12 grupos + eliminación',
            self::League => 'Liga',
        };
    }
}
