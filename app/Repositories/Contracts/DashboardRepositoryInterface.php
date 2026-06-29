<?php

namespace App\Repositories\Contracts;

use App\Models\AuditLog;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\TournamentChampion;
use Illuminate\Database\Eloquent\Collection;

interface DashboardRepositoryInterface
{
    /** @return array<string, int> */
    public function metrics(array $filters): array;

    /** @return Collection<int, GameMatch> */
    public function upcomingMatches(array $filters, int $limit = 6): Collection;

    /** @return Collection<int, GameMatch> */
    public function recentResults(array $filters, int $limit = 6): Collection;

    /** @return Collection<int, TournamentChampion> */
    public function recentChampions(array $filters, int $limit = 5): Collection;

    /** @return Collection<int, Tournament> */
    public function tournaments(array $filters): Collection;

    /** @return Collection<int, AuditLog> */
    public function recentActivity(int $limit = 6): Collection;

    /** @return array<string, int> */
    public function completedByDay(array $filters, int $days = 7): array;
}
