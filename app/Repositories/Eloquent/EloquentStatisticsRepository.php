<?php

namespace App\Repositories\Eloquent;

use App\Enums\AttendanceStatus;
use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
use App\Models\TournamentTeam;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

final class EloquentStatisticsRepository implements StatisticsRepositoryInterface
{
    public function completedMatches(array $filters): Collection
    {
        return GameMatch::query()
            ->with(['scores', 'tournament'])
            ->where('status', MatchStatus::Completed)
            ->where('participant_type', $filters['participant_type'])
            ->whereNotNull('participant_a_id')
            ->whereNotNull('participant_b_id')
            ->whereIn('tournament_id', $filters['visible_tournament_ids'])
            ->when($filters['tournament_id'] ?? null, fn ($query, $id) => $query->where('tournament_id', $id))
            ->when($filters['game'] ?? null, fn ($query, $game) => $query->whereHas('tournament', fn ($query) => $query->where('game', $game)))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('completed_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('completed_at', '<=', $date))
            ->orderBy('completed_at')
            ->orderBy('id')
            ->get();
    }

    public function participants(ParticipantType $type, array $ids): SupportCollection
    {
        if ($ids === []) {
            return collect();
        }

        return ($type === ParticipantType::Individual
            ? Player::query()->whereKey($ids)->get()
            : Team::query()->whereKey($ids)->get())
            ->keyBy('id');
    }

    public function attendanceByTournament(ParticipantType $type, array $tournamentIds): array
    {
        if ($tournamentIds === []) {
            return [];
        }

        $participantColumn = $type === ParticipantType::Individual ? 'player_id' : 'team_id';
        $model = $type === ParticipantType::Individual ? TournamentPlayer::query() : TournamentTeam::query();

        return $model->whereIn('tournament_id', $tournamentIds)
            ->where('attendance_status', '!=', AttendanceStatus::Pending)
            ->get(['tournament_id', $participantColumn, 'attendance_status'])
            ->groupBy('tournament_id')
            ->map(fn ($registrations): array => [
                'present' => $registrations
                    ->where('attendance_status', AttendanceStatus::Present)
                    ->pluck($participantColumn)
                    ->map(fn ($id): int => (int) $id)
                    ->values()
                    ->all(),
            ])->all();
    }

    public function tournaments(array $visibleTournamentIds): Collection
    {
        return Tournament::query()->whereKey($visibleTournamentIds)->orderByDesc('starts_at')->get(['id', 'name', 'slug']);
    }
}
