<?php

namespace App\Services;

use App\Models\Tournament;

final class TournamentRulesService
{
    public function __construct(private readonly TournamentRegistrationService $registrations) {}

    public function document(Tournament $tournament): array
    {
        $participants = $this->registrations->count($tournament);
        $qualifyingMatches = intdiv($participants, 2);
        $mainBracketSize = $this->nextPowerOfTwo(max(2, $qualifyingMatches));
        $bestLosers = max(0, $mainBracketSize - $qualifyingMatches);

        return [
            'participantCount' => $participants,
            'qualifyingMatches' => $qualifyingMatches,
            'mainBracketSize' => $mainBracketSize,
            'bestLoserCount' => $bestLosers,
            'sections' => [
                ['title' => 'Formato competitivo', 'rules' => [
                    "Participan {$participants} jugadores y todos disputan la ronda clasificatoria.",
                    "La clasificatoria contiene {$qualifyingMatches} partidos.",
                    "Avanzan {$qualifyingMatches} ganadores y {$bestLosers} mejores perdedores para completar una llave principal de {$mainBracketSize}.",
                    'Desde la llave principal, cada derrota elimina al participante.',
                ]],
                ['title' => 'Regla de diferencia de tres goles', 'rules' => [
                    'El partido termina automáticamente cuando un jugador alcanza una ventaja de tres goles.',
                    'El marcador oficial conserva exactamente una diferencia máxima de tres goles.',
                    'Ejemplos válidos: 3-0, 4-1 o 5-2.',
                ]],
                ['title' => 'Clasificación de mejores perdedores', 'rules' => [
                    'Primero se compara la mejor diferencia de goles.',
                    'Si persiste el empate, clasifica quien haya anotado más goles.',
                    'Luego se utiliza la mejor semilla del sorteo.',
                    'Como último criterio se utiliza el identificador de inscripción para resolver de forma determinista.',
                    'MatchPoint evita una revancha inmediata entre un mejor perdedor y el jugador que lo derrotó cuando existe alternativa.',
                ]],
                ['title' => 'Empates y definición', 'rules' => [
                    'Los partidos eliminatorios no pueden finalizar empatados.',
                    'Si el videojuego termina empatado, se juega tiempo extra y penales hasta definir un ganador.',
                ]],
                ['title' => 'Equipos y controles', 'rules' => [
                    'Cada jugador utiliza el equipo registrado por la organización y no puede cambiarlo durante el partido.',
                    'Cada participante debe llevar su propio control PS4 o PS5 cargado y en buen estado.',
                ]],
                ['title' => 'Puntualidad y ausencias', 'rules' => [
                    'El participante debe presentarse al menos diez minutos antes de su horario.',
                    'Después de cinco minutos de espera, la organización puede declarar derrota administrativa 3-0.',
                ]],
                ['title' => 'Desconexiones y conducta', 'rules' => [
                    'La organización decide la reanudación ante fallos técnicos y conserva el marcador existente cuando corresponda.',
                    'Una desconexión intencional, trampa, insulto o conducta antideportiva puede causar derrota 3-0 o expulsión.',
                    'Las decisiones del árbitro y de la organización son definitivas.',
                ]],
            ],
        ];
    }

    private function nextPowerOfTwo(int $number): int
    {
        $power = 1;
        while ($power < $number) {
            $power *= 2;
        }

        return $power;
    }
}
