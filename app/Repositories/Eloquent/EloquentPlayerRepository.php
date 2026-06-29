<?php

namespace App\Repositories\Eloquent;

use App\Models\Player;
use App\Repositories\Contracts\PlayerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final class EloquentPlayerRepository implements PlayerRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Player::query()
            ->when(! ($filters['is_admin'] ?? false), fn ($query) => $query->where(function ($query) use ($filters): void {
                $query->where('managed_by', $filters['user_id'])
                    ->orWhereHas('tournaments', fn ($query) => $query->whereKey($filters['visible_tournament_ids']));
            }))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('nickname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['country'] ?? null, fn ($query, $country) => $query->where('country', $country))
            ->when($filters['level'] ?? null, fn ($query, $level) => $query->where('level', $level))
            ->when(
                array_key_exists('is_active', $filters) && $filters['is_active'] !== null,
                fn ($query) => $query->where('is_active', $filters['is_active']),
            )
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countries(): Collection
    {
        return Player::query()
            ->whereNotNull('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');
    }

    public function activeForSelection(): EloquentCollection
    {
        return Player::query()
            ->where('is_active', true)
            ->orderBy('nickname')
            ->get();
    }

    public function create(array $attributes): Player
    {
        return Player::query()->create($attributes);
    }

    public function update(Player $player, array $attributes): Player
    {
        $player->update($attributes);

        return $player->refresh();
    }

    public function delete(Player $player): void
    {
        $player->delete();
    }
}
