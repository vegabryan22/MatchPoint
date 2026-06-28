<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Team;
use App\Repositories\Contracts\PlayerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class PlayerService
{
    public function __construct(private readonly PlayerRepositoryInterface $players) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->players->paginate($filters);
    }

    public function countries(): Collection
    {
        return $this->players->countries();
    }

    public function activeForSelection(): EloquentCollection
    {
        return $this->players->activeForSelection();
    }

    public function forTeamSelection(?Team $team = null): EloquentCollection
    {
        $players = $this->players->activeForSelection();

        if ($team !== null) {
            $players = $players->merge($team->players)->unique('id')->sortBy('nickname')->values();
        }

        return $players;
    }

    public function create(array $data): Player
    {
        /** @var UploadedFile|null $photo */
        $photo = Arr::pull($data, 'photo');
        $newPath = $photo?->store('players', 'public');

        if ($newPath !== null) {
            $data['photo_path'] = $newPath;
        }

        try {
            return DB::transaction(fn (): Player => $this->players->create($data));
        } catch (Throwable $exception) {
            $this->deletePhoto($newPath);

            throw $exception;
        }
    }

    public function update(Player $player, array $data): Player
    {
        /** @var UploadedFile|null $photo */
        $photo = Arr::pull($data, 'photo');
        $oldPath = $player->photo_path;
        $newPath = $photo?->store('players', 'public');

        if ($newPath !== null) {
            $data['photo_path'] = $newPath;
        }

        try {
            $player = DB::transaction(fn (): Player => $this->players->update($player, $data));
        } catch (Throwable $exception) {
            $this->deletePhoto($newPath);

            throw $exception;
        }

        if ($newPath !== null) {
            $this->deletePhoto($oldPath);
        }

        return $player;
    }

    public function toggleStatus(Player $player): Player
    {
        return $this->players->update($player, ['is_active' => ! $player->is_active]);
    }

    public function delete(Player $player): void
    {
        $photoPath = $player->photo_path;

        DB::transaction(function () use ($player): void {
            $this->players->delete($player);
        });
        $this->deletePhoto($photoPath);
    }

    private function deletePhoto(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }
}
