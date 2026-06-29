<?php

namespace App\Repositories\Eloquent;

use App\Models\GameClub;
use App\Repositories\Contracts\GameClubRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EloquentGameClubRepository implements GameClubRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return GameClub::query()
            ->with('availabilities')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['game'] ?? null, fn ($query, $game) => $query->whereHas('availabilities', fn ($query) => $query->where('game', $game)))
            ->when($filters['team_type'] ?? null, fn ($query, $type) => $query->where('team_type', $type))
            ->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function create(array $attributes, array $games): GameClub
    {
        return DB::transaction(function () use ($attributes, $games): GameClub {
            $club = GameClub::query()->create($attributes);
            $this->syncGames($club, $games);

            return $club->load('availabilities');
        });
    }

    public function update(GameClub $club, array $attributes, array $games): GameClub
    {
        return DB::transaction(function () use ($club, $attributes, $games): GameClub {
            $club->update($attributes);
            $this->syncGames($club, $games);

            return $club->refresh()->load('availabilities');
        });
    }

    public function delete(GameClub $club): void
    {
        $club->delete();
    }

    public function updateOrCreate(array $identity, array $attributes, array $games): GameClub
    {
        return DB::transaction(function () use ($identity, $attributes, $games): GameClub {
            $club = GameClub::query()->updateOrCreate($identity, $attributes);
            $existing = $club->availabilities()->pluck('game')->all();
            $this->syncGames($club, array_values(array_unique([...$existing, ...$games])));

            return $club->load('availabilities');
        });
    }

    private function syncGames(GameClub $club, array $games): void
    {
        $club->availabilities()->delete();
        $club->availabilities()->createMany(array_map(fn (string $game): array => ['game' => $game], $games));
    }
}
