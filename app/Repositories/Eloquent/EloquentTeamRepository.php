<?php

namespace App\Repositories\Eloquent;

use App\Models\Team;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTeamRepository implements TeamRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Team::query()
            ->when(! ($filters['is_admin'] ?? false), fn ($query) => $query->where(function ($query) use ($filters): void {
                $query->where('managed_by', $filters['user_id'])
                    ->orWhereHas('tournaments', fn ($query) => $query->whereKey($filters['visible_tournament_ids']));
            }))
            ->withCount('players')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when(
                array_key_exists('is_active', $filters) && $filters['is_active'] !== null,
                fn ($query) => $query->where('is_active', $filters['is_active']),
            )
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Team
    {
        return Team::query()->create($attributes);
    }

    public function update(Team $team, array $attributes): Team
    {
        $team->update($attributes);

        return $team->refresh();
    }

    public function delete(Team $team): void
    {
        $team->delete();
    }
}
