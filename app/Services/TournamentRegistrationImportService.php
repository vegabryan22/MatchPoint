<?php

namespace App\Services;

use App\Enums\ParticipantType;
use App\Enums\RegistrationSource;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class TournamentRegistrationImportService
{
    private const MAX_ROWS = 5000;

    public function __construct(
        private readonly TournamentRegistrationService $registrations,
        private readonly AuditService $audit,
    ) {}

    public function import(Tournament $tournament, string $path, User $actor): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el archivo CSV.');
        }

        try {
            $headers = $this->headers($handle);
            $this->validateHeaders($tournament, $headers);
            $result = ['total' => 0, 'imported' => 0, 'failed' => 0, 'errors' => []];

            while (($row = fgetcsv($handle)) !== false && $result['total'] < self::MAX_ROWS) {
                if ($this->emptyRow($row)) {
                    continue;
                }

                $result['total']++;
                $values = array_slice(array_pad($row, count($headers), null), 0, count($headers));
                $data = array_combine($headers, $values);

                try {
                    $participantId = $this->resolveParticipant($tournament, $data);
                    $this->registrations->register($tournament, $participantId, $actor, RegistrationSource::Csv);
                    $result['imported']++;
                } catch (Throwable $exception) {
                    $result['failed']++;
                    $result['errors'][] = [
                        'row' => $result['total'] + 1,
                        'message' => $exception instanceof ValidationException
                            ? collect($exception->errors())->flatten()->first()
                            : $exception->getMessage(),
                    ];
                }
            }
        } finally {
            fclose($handle);
        }

        $this->audit->record('registration.imported', $tournament, [], [
            'total' => $result['total'],
            'imported' => $result['imported'],
            'failed' => $result['failed'],
        ], $actor->id);

        return $result;
    }

    /** @param resource $handle */
    private function headers($handle): array
    {
        $row = fgetcsv($handle);

        if ($row === false) {
            throw ValidationException::withMessages(['file' => 'El CSV está vacío.']);
        }

        return array_map(
            fn ($header): string => mb_strtolower(trim((string) $header, "\xEF\xBB\xBF \t\n\r\0\x0B")),
            $row,
        );
    }

    private function validateHeaders(Tournament $tournament, array $headers): void
    {
        $required = $tournament->participant_type === ParticipantType::Individual
            ? ['nickname', 'email']
            : ['name'];

        if (array_diff($required, $headers) !== []) {
            throw ValidationException::withMessages([
                'file' => 'Encabezados requeridos: '.implode(', ', $required).'.',
            ]);
        }
    }

    private function resolveParticipant(Tournament $tournament, array $data): int
    {
        if ($tournament->participant_type === ParticipantType::Individual) {
            $player = Player::query()
                ->where('nickname', trim((string) ($data['nickname'] ?? '')))
                ->orWhere('email', trim((string) ($data['email'] ?? '')))
                ->first();

            if ($player === null) {
                throw ValidationException::withMessages(['participant' => 'Jugador no encontrado.']);
            }

            return $player->id;
        }

        $team = Team::query()->where('name', trim((string) ($data['name'] ?? '')))->first();

        if ($team === null) {
            throw ValidationException::withMessages(['participant' => 'Equipo no encontrado.']);
        }

        return $team->id;
    }

    private function emptyRow(array $row): bool
    {
        return collect($row)->every(fn ($value): bool => blank($value));
    }
}
