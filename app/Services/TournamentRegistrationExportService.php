<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ParticipantType;
use App\Models\GameClub;
use App\Models\Tournament;
use App\Repositories\Contracts\TournamentRegistrationRepositoryInterface;
use Illuminate\Filesystem\Filesystem;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

final class TournamentRegistrationExportService
{
    public function __construct(
        private readonly TournamentRegistrationRepositoryInterface $registrations,
        private readonly Filesystem $files,
    ) {}

    /** @param resource $stream */
    public function writeCsv(Tournament $tournament, $stream): void
    {
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $this->headers($tournament));

        foreach ($this->rows($tournament) as $row) {
            fputcsv($stream, $row);
        }
    }

    public function createXlsx(Tournament $tournament): string
    {
        $directory = storage_path('app/tmp/registration-exports');
        $this->files->ensureDirectoryExists($directory);
        $path = $directory.DIRECTORY_SEPARATOR.$tournament->slug.'-'.now()->format('YmdHis').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($this->headers($tournament)));

        foreach ($this->rows($tournament) as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $path;
    }

    private function headers(Tournament $tournament): array
    {
        return $tournament->participant_type === ParticipantType::Individual
            ? ['Apodo', 'Nombre', 'Club del juego', 'Correo', 'País', 'Nivel académico', 'Control', 'Nivel de juego', 'Asistencia', 'Origen', 'Fecha de inscripción']
            : ['Equipo', 'Club del juego', 'Integrantes', 'Asistencia', 'Origen', 'Fecha de inscripción'];
    }

    private function rows(Tournament $tournament): array
    {
        $participants = $this->registrations->all($tournament);

        if ($tournament->participant_type === ParticipantType::Team) {
            $participants->loadCount('players');
        }
        $clubs = GameClub::query()->whereIn('id', $participants->pluck('pivot.game_club_id')->filter()->unique())->get()->keyBy('id');

        return $participants->map(function ($participant) use ($tournament, $clubs): array {
            $source = (string) $participant->pivot->source;
            $registeredAt = (string) $participant->pivot->registered_at;
            $attendance = AttendanceStatus::from($participant->pivot->attendance_status)->label();
            $clubName = $clubs->get($participant->pivot->game_club_id)?->name;

            if ($tournament->participant_type === ParticipantType::Individual) {
                return [
                    $participant->nickname,
                    $participant->name,
                    $clubName,
                    $participant->email,
                    $participant->country,
                    $participant->pivot->academic_level,
                    $participant->pivot->controller_platform,
                    $participant->level->label(),
                    $attendance,
                    $source,
                    $registeredAt,
                ];
            }

            return [$participant->name, $clubName, $participant->players_count, $attendance, $source, $registeredAt];
        })->all();
    }
}
