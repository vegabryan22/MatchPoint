<?php

namespace App\Repositories\Contracts;

use App\Models\GameClub;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GameClubRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function create(array $attributes, array $games): GameClub;

    public function update(GameClub $club, array $attributes, array $games): GameClub;

    public function delete(GameClub $club): void;

    public function updateOrCreate(array $identity, array $attributes, array $games): GameClub;
}
