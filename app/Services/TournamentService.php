<?php

namespace App\Services;

use App\Enums\GameType;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Models\User;
use App\Repositories\Contracts\TournamentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class TournamentService
{
    /** @var array<string, list<TournamentStatus>> */
    private const TRANSITIONS = [
        TournamentStatus::Draft->value => [TournamentStatus::Registration, TournamentStatus::Cancelled],
        TournamentStatus::Registration->value => [TournamentStatus::Draft, TournamentStatus::InProgress, TournamentStatus::Cancelled],
        TournamentStatus::InProgress->value => [TournamentStatus::Finished, TournamentStatus::Cancelled],
        TournamentStatus::Finished->value => [],
        TournamentStatus::Cancelled->value => [TournamentStatus::Draft],
    ];

    public function __construct(
        private readonly TournamentRepositoryInterface $tournaments,
        private readonly AuditService $audit,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->tournaments->paginate($filters);
    }

    public function create(array $data, User $creator): Tournament
    {
        $this->normalizeCustomGame($data);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['status'] = TournamentStatus::Draft;
        $data['created_by'] = $creator->getKey();

        return DB::transaction(fn (): Tournament => $this->tournaments->create($data));
    }

    public function update(Tournament $tournament, array $data): Tournament
    {
        $this->ensureEditable($tournament);
        $this->normalizeCustomGame($data);

        return DB::transaction(fn (): Tournament => $this->tournaments->update($tournament, $data));
    }

    public function duplicate(Tournament $tournament, User $creator): Tournament
    {
        $start = $tournament->starts_at->isFuture()
            ? $tournament->starts_at->copy()->addWeek()
            : now()->addWeek();
        $duration = $tournament->ends_at?->diffInSeconds($tournament->starts_at);
        $name = "Copia de {$tournament->name}";

        $copy = DB::transaction(function () use ($tournament, $creator, $start, $duration, $name): Tournament {
            return $this->tournaments->create([
                'created_by' => $creator->getKey(),
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'description' => $tournament->description,
                'game' => $tournament->game,
                'custom_game' => $tournament->custom_game,
                'participant_type' => $tournament->participant_type,
                'max_participants' => $tournament->max_participants,
                'format' => $tournament->format,
                'best_of' => $tournament->best_of,
                'status' => TournamentStatus::Draft,
                'registration_starts_at' => null,
                'registration_ends_at' => null,
                'starts_at' => $start,
                'ends_at' => $duration === null ? null : $start->copy()->addSeconds($duration),
            ]);
        });

        $this->audit->record('tournament.duplicated', $tournament, [], ['copy_id' => $copy->id]);

        return $copy;
    }

    public function transition(Tournament $tournament, TournamentStatus $target): Tournament
    {
        if (! in_array($target, $this->allowedTransitions($tournament), true)) {
            throw ValidationException::withMessages([
                'status' => "No se puede cambiar de {$tournament->status->label()} a {$target->label()}.",
            ]);
        }

        $previous = $tournament->status;
        $changes = ['status' => $target];

        if ($target === TournamentStatus::Finished && $tournament->ends_at === null) {
            $changes['ends_at'] = now();
        }

        $tournament = DB::transaction(fn (): Tournament => $this->tournaments->update($tournament, $changes));
        $this->audit->record('tournament.status_changed', $tournament, ['status' => $previous->value], ['status' => $target->value]);

        return $tournament;
    }

    /** @return list<TournamentStatus> */
    public function allowedTransitions(Tournament $tournament): array
    {
        return self::TRANSITIONS[$tournament->status->value];
    }

    public function delete(Tournament $tournament): void
    {
        if (! in_array($tournament->status, [TournamentStatus::Draft, TournamentStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'tournament' => 'Sólo se pueden eliminar torneos en borrador o cancelados.',
            ]);
        }

        DB::transaction(function () use ($tournament): void {
            $this->tournaments->delete($tournament);
        });
    }

    private function ensureEditable(Tournament $tournament): void
    {
        if (! in_array($tournament->status, [TournamentStatus::Draft, TournamentStatus::Registration], true)) {
            throw ValidationException::withMessages([
                'tournament' => 'La configuración sólo puede editarse en borrador o inscripciones.',
            ]);
        }
    }

    private function normalizeCustomGame(array &$data): void
    {
        if (($data['game'] ?? null) !== GameType::Other->value) {
            $data['custom_game'] = null;
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'torneo';
        $slug = $base;
        $suffix = 2;

        while ($this->tournaments->slugExists($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
