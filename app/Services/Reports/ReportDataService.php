<?php

namespace App\Services\Reports;

use App\Enums\AttendanceStatus;
use App\Enums\ParticipantType;
use App\Enums\ReportType;
use App\Models\Tournament;
use App\Models\TournamentChampion;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use App\Repositories\Contracts\TournamentRegistrationRepositoryInterface;
use App\Services\GroupStageService;
use App\Services\StatisticsService;

final class ReportDataService
{
    public function __construct(
        private readonly TournamentRegistrationRepositoryInterface $registrations,
        private readonly StatisticsService $statistics,
        private readonly StatisticsRepositoryInterface $statisticsRepository,
        private readonly GroupStageService $groups,
    ) {}

    public function build(ReportType $type, array $filters): array
    {
        $filters['visible_tournament_ids'] ??= Tournament::query()->pluck('id')->all();
        $tournament = isset($filters['tournament_id'])
            ? Tournament::query()->whereKey($filters['visible_tournament_ids'])->findOrFail($filters['tournament_id'])
            : null;

        return match ($type) {
            ReportType::Summary => $this->summary($tournament),
            ReportType::Registrations => $this->registrations($tournament),
            ReportType::Results => $this->results($tournament),
            ReportType::Standings => $this->standings($tournament),
            ReportType::Statistics => $this->statistics($filters),
            ReportType::Champions => $this->champions($filters),
        };
    }

    private function summary(Tournament $tournament): array
    {
        return $this->data('Resumen · '.$tournament->name, ['Campo', 'Valor'], [
            ['Juego', $tournament->gameLabel()], ['Formato', $tournament->format->label()],
            ['Modalidad', $tournament->participant_type->label()], ['Estado', $tournament->status->label()],
            ['Participantes', $this->registrations->count($tournament)], ['Partidos', $tournament->matches()->count()],
            ['Presentes', $this->registrations->all($tournament)->where('pivot.attendance_status', AttendanceStatus::Present->value)->count()],
            ['Finalizados', $tournament->matches()->where('status', 'completed')->count()],
        ]);
    }

    private function registrations(Tournament $tournament): array
    {
        $rows = $this->registrations->all($tournament)->map(fn ($participant): array => [
            $participant->pivot->seed ?? '—', $participant->nickname ?? $participant->name,
            $participant->email ?? '—', AttendanceStatus::from($participant->pivot->attendance_status)->label(), $participant->pivot->registered_at,
        ])->all();

        return $this->data('Inscripciones · '.$tournament->name, ['Semilla', 'Participante', 'Correo', 'Asistencia', 'Fecha'], $rows);
    }

    private function results(Tournament $tournament): array
    {
        $participants = $this->registrations->all($tournament)->keyBy('id');
        $rows = $tournament->matches()->with(['round', 'scores'])->get()->map(function ($match) use ($participants): array {
            $a = $participants->get($match->participant_a_id);
            $b = $participants->get($match->participant_b_id);

            return [$match->round?->name, $a?->nickname ?? $a?->name ?? 'Por definir', $match->scores->sum('participant_a_score'), $match->scores->sum('participant_b_score'), $b?->nickname ?? $b?->name ?? 'Por definir', $match->status->label()];
        })->all();

        return $this->data('Resultados · '.$tournament->name, ['Ronda', 'Participante A', 'A', 'B', 'Participante B', 'Estado'], $rows);
    }

    private function standings(Tournament $tournament): array
    {
        $details = $this->groups->details($tournament);
        $rows = [];
        foreach ($details['groups'] as $group) {
            foreach ($details['standings'][$group->id] as $row) {
                $rows[] = [$group->name, $row['position'], $row['name'], $row['played'], $row['wins'], $row['draws'], $row['losses'], $row['goal_difference'], $row['points']];
            }
        }

        return $this->data('Posiciones · '.$tournament->name, ['Grupo', '#', 'Participante', 'PJ', 'V', 'E', 'D', 'DG', 'Pts'], $rows);
    }

    private function statistics(array $filters): array
    {
        $type = ParticipantType::tryFrom($filters['participant_type'] ?? '') ?? ParticipantType::Individual;
        $ranking = $this->statistics->ranking(['participant_type' => $type->value, 'visible_tournament_ids' => $filters['visible_tournament_ids']])['ranking'];
        $rows = $ranking->map(fn ($row): array => [$row['rank'], $row['name'], $row['played'], $row['wins'], $row['draws'], $row['losses'], $row['goals_for'], $row['goals_against'], $row['goal_difference'], $row['streak']])->all();

        return $this->data('Estadísticas · '.$type->label(), ['#', 'Participante', 'PJ', 'V', 'E', 'D', 'GF', 'GC', 'DG', 'Racha'], $rows);
    }

    private function champions(array $filters): array
    {
        $records = TournamentChampion::query()->with('tournament')->whereIn('tournament_id', $filters['visible_tournament_ids'])->latest('crowned_at')->get();
        $maps = collect(ParticipantType::cases())->mapWithKeys(fn ($type): array => [$type->value => $this->statisticsRepository->participants($type, $records->where('participant_type', $type)->pluck('participant_id')->all())]);
        $rows = $records->map(fn ($record): array => [$maps[$record->participant_type->value]->get($record->participant_id)?->nickname ?? $maps[$record->participant_type->value]->get($record->participant_id)?->name ?? 'Participante', $record->tournament->name, $record->tournament->gameLabel(), $record->crowned_at->format('d/m/Y')])->all();

        return $this->data('Campeones históricos', ['Campeón', 'Torneo', 'Juego', 'Fecha'], $rows);
    }

    private function data(string $title, array $headers, array $rows): array
    {
        return compact('title', 'headers', 'rows') + ['generated_at' => now()];
    }
}
