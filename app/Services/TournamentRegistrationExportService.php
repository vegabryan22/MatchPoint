<?php

namespace App\Services;

use App\Enums\ParticipantType;
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
            ? ['Apodo', 'Nombre', 'Correo', 'País', 'Nivel', 'Origen', 'Fecha de inscripción']
            : ['Equipo', 'Integrantes', 'Origen', 'Fecha de inscripción'];
    }

    private function rows(Tournament $tournament): array
    {
        $participants = $this->registrations->all($tournament);

        if ($tournament->participant_type === ParticipantType::Team) {
            $participants->loadCount('players');
        }

        return $participants->map(function ($participant) use ($tournament): array {
            $source = (string) $participant->pivot->source;
            $registeredAt = (string) $participant->pivot->registered_at;

            if ($tournament->participant_type === ParticipantType::Individual) {
                return [
                    $participant->nickname,
                    $participant->name,
                    $participant->email,
                    $participant->country,
                    $participant->level->label(),
                    $source,
                    $registeredAt,
                ];
            }

            return [$participant->name, $participant->players_count, $source, $registeredAt];
        })->all();
    }
}
