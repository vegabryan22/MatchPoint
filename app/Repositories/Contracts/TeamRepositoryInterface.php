<?php

namespace App\Repositories\Contracts;

use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TeamRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Team> */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function create(array $attributes): Team;

    public function update(Team $team, array $attributes): Team;

    public function delete(Team $team): void;
}
