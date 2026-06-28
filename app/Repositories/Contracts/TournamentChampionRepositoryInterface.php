<?php

namespace App\Repositories\Contracts;

use App\Models\Tournament;
use App\Models\TournamentChampion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TournamentChampionRepositoryInterface
{
    public function findForTournament(Tournament $tournament): ?TournamentChampion;

    public function updateOrCreate(Tournament $tournament, array $attributes): TournamentChampion;

    public function deleteForTournament(Tournament $tournament): void;

    /** @return LengthAwarePaginator<int, TournamentChampion> */
    public function paginate(array $filters, int $perPage = 18): LengthAwarePaginator;

    /** @return list<int> */
    public function years(): array;
}
