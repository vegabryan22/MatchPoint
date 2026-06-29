<?php

namespace App\Services;

use App\Models\GameClub;
use App\Repositories\Contracts\GameClubRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Throwable;

final class GameClubService
{
    public function __construct(private readonly GameClubRepositoryInterface $clubs) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->clubs->paginate($filters);
    }

    public function create(array $data): GameClub
    {
        $crest = Arr::pull($data, 'crest');
        $games = Arr::pull($data, 'games', []);
        $path = $crest instanceof UploadedFile ? $crest->store('game-clubs', 'public') : null;
        if ($path !== null) {
            $data['crest_path'] = $path;
        }

        try {
            return $this->clubs->create($data, $games);
        } catch (Throwable $exception) {
            if ($path) {
                $this->deleteCrest($path);
            }

            throw $exception;
        }
    }

    public function update(GameClub $club, array $data): GameClub
    {
        $crest = Arr::pull($data, 'crest');
        $games = Arr::pull($data, 'games', []);
        $oldPath = $club->crest_path;
        $newPath = $crest instanceof UploadedFile ? $crest->store('game-clubs', 'public') : null;
        if ($newPath !== null) {
            $data['crest_path'] = $newPath;
        }
        try {
            $club = $this->clubs->update($club, $data, $games);
        } catch (Throwable $exception) {
            if ($newPath) {
                $this->deleteCrest($newPath);
            }

            throw $exception;
        }
        if ($newPath && $oldPath) {
            $this->deleteCrest($oldPath);
        }

        return $club;
    }

    public function delete(GameClub $club): void
    {
        $path = $club->crest_path;
        $this->clubs->delete($club);
        if ($path) {
            $this->deleteCrest($path);
        }
    }

    private function deleteCrest(string $path): void
    {
        $relativePath = ltrim(str_replace(['..', '\\'], ['', '/'], $path), '/');
        File::delete(storage_path('app/public/'.$relativePath));
    }
}
