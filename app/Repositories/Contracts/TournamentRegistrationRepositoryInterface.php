<?php

namespace App\Repositories\Contracts;

use App\Enums\RegistrationSource;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TournamentRegistrationRepositoryInterface
{
    public function count(Tournament $tournament): int;

    /** @return LengthAwarePaginator<int, Player|Team> */
    public function paginate(Tournament $tournament, ?string $search, int $perPage = 15): LengthAwarePaginator;

    /** @return Collection<int, Player|Team> */
    public function candidates(Tournament $tournament, ?string $search): Collection;

    /** @return Collection<int, Player|Team> */
    public function all(Tournament $tournament): Collection;

    public function isRegistered(Tournament $tournament, int $participantId): bool;

    public function register(Tournament $tournament, int $participantId, int $userId, RegistrationSource $source): void;

    public function remove(Tournament $tournament, int $participantId): void;
}
