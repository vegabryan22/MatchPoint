<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class TeamService
{
    public function __construct(
        private readonly TeamRepositoryInterface $teams,
        private readonly AuditService $audit,
        private readonly TournamentAccessService $access,
    ) {}

    public function paginate(array $filters, User $user): LengthAwarePaginator
    {
        $filters['user_id'] = $user->id;
        $filters['is_admin'] = $user->isAdministrator();
        $filters['visible_tournament_ids'] = $this->access->visibleQuery($user)->pluck('id')->all();

        return $this->teams->paginate($filters);
    }

    public function details(Team $team): Team
    {
        return $team->load(['players' => fn ($query) => $query->orderByDesc('player_team.is_captain')->orderBy('nickname')]);
    }

    public function create(array $data, ?User $actor = null): Team
    {
        $data['managed_by'] = $actor?->id;
        /** @var UploadedFile|null $logo */
        $logo = Arr::pull($data, 'logo');
        $playerIds = array_map('intval', Arr::pull($data, 'player_ids', []));
        $captainId = $this->captainId(Arr::pull($data, 'captain_id'));
        $newPath = $logo?->store('teams', 'public');

        if ($newPath !== null) {
            $data['logo_path'] = $newPath;
        }

        try {
            return DB::transaction(function () use ($data, $playerIds, $captainId): Team {
                $team = $this->teams->create($data);
                $this->syncRoster($team, $playerIds, $captainId, []);

                return $this->details($team);
            });
        } catch (Throwable $exception) {
            $this->deleteLogo($newPath);

            throw $exception;
        }
    }

    public function update(Team $team, array $data): Team
    {
        /** @var UploadedFile|null $logo */
        $logo = Arr::pull($data, 'logo');
        $playerIds = array_map('intval', Arr::pull($data, 'player_ids', []));
        $captainId = $this->captainId(Arr::pull($data, 'captain_id'));
        $oldPath = $team->logo_path;
        $newPath = $logo?->store('teams', 'public');

        if ($newPath !== null) {
            $data['logo_path'] = $newPath;
        }

        try {
            $team = DB::transaction(function () use ($team, $data, $playerIds, $captainId): Team {
                $oldRoster = $team->players()->pluck('players.id')->all();
                $team = $this->teams->update($team, $data);
                $this->syncRoster($team, $playerIds, $captainId, $oldRoster);

                return $this->details($team);
            });
        } catch (Throwable $exception) {
            $this->deleteLogo($newPath);

            throw $exception;
        }

        if ($newPath !== null) {
            $this->deleteLogo($oldPath);
        }

        return $team;
    }

    public function toggleStatus(Team $team): Team
    {
        return $this->teams->update($team, ['is_active' => ! $team->is_active]);
    }

    public function delete(Team $team): void
    {
        $logoPath = $team->logo_path;

        DB::transaction(function () use ($team): void {
            $this->teams->delete($team);
        });
        $this->deleteLogo($logoPath);
    }

    private function syncRoster(Team $team, array $playerIds, ?int $captainId, array $oldRoster): void
    {
        $roster = collect($playerIds)
            ->mapWithKeys(fn (int|string $playerId): array => [
                (int) $playerId => ['is_captain' => (int) $playerId === $captainId],
            ])
            ->all();

        $team->players()->sync($roster);
        $this->audit->record('roster.updated', $team, ['player_ids' => $oldRoster], [
            'player_ids' => array_map('intval', $playerIds),
            'captain_id' => $captainId,
        ]);
    }

    private function deleteLogo(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }

    private function captainId(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
