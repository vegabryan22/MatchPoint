<?php

namespace App\Repositories\Eloquent;

use App\Enums\MatchStatus;
use App\Enums\TournamentStatus;
use App\Models\AuditLog;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Score;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentChampion;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class EloquentDashboardRepository implements DashboardRepositoryInterface
{
    public function metrics(array $filters): array
    {
        $matches = fn (): Builder => $this->matchQuery($filters);
        $goals = Score::query()
            ->whereHas('gameMatch', function (Builder $query) use ($filters): void {
                $this->applyMatchFilters($query, $filters, true);
                $query->where('status', MatchStatus::Completed);
            })
            ->get(['participant_a_score', 'participant_b_score'])
            ->sum(fn (Score $score): int => $score->participant_a_score + $score->participant_b_score);

        return [
            'players' => Player::query()->count(),
            'active_players' => Player::query()->where('is_active', true)->count(),
            'teams' => Team::query()->count(),
            'active_teams' => Team::query()->where('is_active', true)->count(),
            'tournaments' => Tournament::query()
                ->when($filters['participant_type'] ?? null, fn ($query, $type) => $query->where('participant_type', $type))
                ->when($filters['tournament_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
                ->count(),
            'pending_matches' => $this->matchQuery($filters, false)->where('status', MatchStatus::Pending)->count(),
            'completed_matches' => $matches()->where('status', MatchStatus::Completed)->count(),
            'total_goals' => $goals,
        ];
    }

    public function upcomingMatches(array $filters, int $limit = 6): Collection
    {
        return $this->matchQuery($filters, false)
            ->with(['tournament', 'round'])
            ->where('status', MatchStatus::Pending)
            ->whereNotNull('participant_a_id')
            ->whereNotNull('participant_b_id')
            ->whereHas('tournament', fn ($query) => $query->where('status', TournamentStatus::InProgress))
            ->orderByRaw('scheduled_at IS NULL')
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function recentResults(array $filters, int $limit = 6): Collection
    {
        return $this->matchQuery($filters)
            ->with(['tournament', 'round', 'scores'])
            ->where('status', MatchStatus::Completed)
            ->latest('completed_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function recentChampions(array $filters, int $limit = 5): Collection
    {
        return TournamentChampion::query()
            ->with('tournament')
            ->when($filters['participant_type'] ?? null, fn ($query, $type) => $query->where('participant_type', $type))
            ->when($filters['tournament_id'] ?? null, fn ($query, $id) => $query->where('tournament_id', $id))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('crowned_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('crowned_at', '<=', $date))
            ->latest('crowned_at')
            ->limit($limit)
            ->get();
    }

    public function tournaments(): Collection
    {
        return Tournament::query()->orderByDesc('starts_at')->get(['id', 'name', 'slug']);
    }

    public function recentActivity(int $limit = 6): Collection
    {
        return AuditLog::query()->with('user')->latest('id')->limit($limit)->get();
    }

    public function completedByDay(array $filters, int $days = 7): array
    {
        $start = CarbonImmutable::today()->subDays($days - 1);
        $counts = $this->matchQuery($filters)
            ->where('status', MatchStatus::Completed)
            ->whereDate('completed_at', '>=', $start->toDateString())
            ->whereDate('completed_at', '<=', CarbonImmutable::today()->toDateString())
            ->get(['completed_at'])
            ->filter(fn (GameMatch $match): bool => $match->completed_at !== null)
            ->countBy(fn (GameMatch $match): string => $match->completed_at->toDateString());

        return collect(range(0, $days - 1))->mapWithKeys(function (int $offset) use ($start, $counts): array {
            $date = $start->addDays($offset);

            return [$date->format('d/m') => $counts->get($date->toDateString(), 0)];
        })->all();
    }

    private function matchQuery(array $filters, bool $completedDates = true): Builder
    {
        $query = GameMatch::query();
        $this->applyMatchFilters($query, $filters, $completedDates);

        return $query;
    }

    private function applyMatchFilters(Builder $query, array $filters, bool $completedDates): void
    {
        $query
            ->when($filters['participant_type'] ?? null, fn ($query, $type) => $query->where('participant_type', $type))
            ->when($filters['tournament_id'] ?? null, fn ($query, $id) => $query->where('tournament_id', $id));

        $dateColumn = $completedDates ? 'completed_at' : 'scheduled_at';
        $query
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate($dateColumn, '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate($dateColumn, '<=', $date));
    }
}
