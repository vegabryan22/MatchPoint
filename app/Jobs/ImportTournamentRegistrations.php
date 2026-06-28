<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Models\User;
use App\Services\TournamentRegistrationImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

final class ImportTournamentRegistrations implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        private readonly int $tournamentId,
        private readonly int $userId,
        private readonly string $path,
    ) {}

    public function handle(TournamentRegistrationImportService $imports): void
    {
        $tournament = Tournament::query()->findOrFail($this->tournamentId);
        $user = User::query()->findOrFail($this->userId);

        try {
            $imports->import($tournament, Storage::disk('local')->path($this->path), $user);
        } finally {
            Storage::disk('local')->delete($this->path);
        }
    }
}
