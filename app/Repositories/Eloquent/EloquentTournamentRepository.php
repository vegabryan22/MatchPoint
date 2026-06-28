<?php

namespace App\Repositories\Eloquent;

use App\Models\Tournament;
use App\Repositories\Contracts\TournamentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTournamentRepository implements TournamentRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Tournament::query()
            ->with('creator')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['game'] ?? null, fn ($query, $game) => $query->where('game', $game))
            ->when($filters['format'] ?? null, fn ($query, $format) => $query->where('format', $format))
            ->when($filters['participant_type'] ?? null, fn ($query, $type) => $query->where('participant_type', $type))
            ->orderByDesc('starts_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Tournament
    {
        return Tournament::query()->create($attributes);
    }

    public function update(Tournament $tournament, array $attributes): Tournament
    {
        $tournament->update($attributes);

        return $tournament->refresh();
    }

    public function delete(Tournament $tournament): void
    {
        $tournament->delete();
    }

    public function slugExists(string $slug): bool
    {
        return Tournament::query()->withTrashed()->where('slug', $slug)->exists();
    }
}
