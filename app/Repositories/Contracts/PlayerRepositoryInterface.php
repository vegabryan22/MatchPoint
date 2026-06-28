<?php

namespace App\Repositories\Contracts;

use App\Models\Player;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

interface PlayerRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Player> */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    /** @return Collection<int, string> */
    public function countries(): Collection;

    /** @return EloquentCollection<int, Player> */
    public function activeForSelection(): EloquentCollection;

    public function create(array $attributes): Player;

    public function update(Player $player, array $attributes): Player;

    public function delete(Player $player): void;
}
