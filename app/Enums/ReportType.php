<?php

namespace App\Enums;

enum ReportType: string
{
    case Summary = 'summary';
    case Registrations = 'registrations';
    case Results = 'results';
    case Standings = 'standings';
    case Statistics = 'statistics';
    case Champions = 'champions';

    public function label(): string
    {
        return match ($this) {
            self::Summary => 'Resumen del torneo', self::Registrations => 'Inscripciones',
            self::Results => 'Calendario y resultados', self::Standings => 'Posiciones',
            self::Statistics => 'Estadísticas', self::Champions => 'Campeones históricos',
        };
    }

    public function requiresTournament(): bool
    {
        return ! in_array($this, [self::Statistics, self::Champions], true);
    }
}
