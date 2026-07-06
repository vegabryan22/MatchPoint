<?php

namespace App\Repositories\Contracts;

use App\Enums\ParticipantType;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface StatisticsRepositoryInterface
{
    /** @return Collection<int, GameMatch> */
    public function completedMatches(array $filters): Collection;

    /** @return SupportCollection<int, Player|Team> */
    public function participants(ParticipantType $type, array $ids): SupportCollection;

    /** @return array<int, array{present: list<int>}> */
    public function attendanceByTournament(ParticipantType $type, array $tournamentIds): array;

    /** @return Collection<int, Tournament> */
    public function tournaments(array $visibleTournamentIds): Collection;
}
