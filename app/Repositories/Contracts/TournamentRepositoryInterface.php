<?php

namespace App\Repositories\Contracts;

use App\Models\Tournament;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TournamentRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Tournament> */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function create(array $attributes): Tournament;

    public function update(Tournament $tournament, array $attributes): Tournament;

    public function delete(Tournament $tournament): void;

    public function slugExists(string $slug): bool;
}
