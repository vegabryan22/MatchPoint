<?php

namespace App\Services;

use App\Enums\ParticipantType;
use App\Models\AuditLog;
use App\Models\GameMatch;
use App\Models\TournamentChampion;
use App\Models\User;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use Illuminate\Support\Collection;

final class DashboardService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboard,
        private readonly StatisticsRepositoryInterface $statistics,
        private readonly TournamentAccessService $access,
    ) {}

    public function summary(array $filters, User $user): array
    {
        $filters['visible_tournament_ids'] = $this->access->visibleQuery($user)->pluck('id')->all();
        $filters['is_admin'] = $user->isAdministrator();
        $upcomingMatches = $this->dashboard->upcomingMatches($filters);
        $recentResults = $this->dashboard->recentResults($filters);
        $recentChampions = $this->dashboard->recentChampions($filters);
        $this->resolveMatchParticipants($upcomingMatches->concat($recentResults));
        $this->resolveChampionParticipants($recentChampions);

        return [
            'metrics' => $this->dashboard->metrics($filters),
            'upcomingMatches' => $upcomingMatches,
            'recentResults' => $recentResults,
            'recentChampions' => $recentChampions,
            'activityByDay' => $this->dashboard->completedByDay($filters),
            'recentActivity' => $user->can('viewAny', AuditLog::class)
                ? $this->dashboard->recentActivity()
                : collect(),
            'tournaments' => $this->dashboard->tournaments($filters),
            'filters' => $filters,
            'generatedAt' => now(),
        ];
    }

    private function resolveMatchParticipants(Collection $matches): void
    {
        foreach (ParticipantType::cases() as $type) {
            $typedMatches = $matches->where('participant_type', $type);
            $ids = $typedMatches
                ->flatMap(fn (GameMatch $match): array => [$match->participant_a_id, $match->participant_b_id])
                ->filter()
                ->unique()
                ->values()
                ->all();
            $participants = $this->statistics->participants($type, $ids);

            $typedMatches->each(function (GameMatch $match) use ($participants): void {
                $match->setRelation('participantAResolved', $participants->get($match->participant_a_id));
                $match->setRelation('participantBResolved', $participants->get($match->participant_b_id));
            });
        }
    }

    private function resolveChampionParticipants(Collection $champions): void
    {
        foreach (ParticipantType::cases() as $type) {
            $typedChampions = $champions->where('participant_type', $type);
            $participants = $this->statistics->participants($type, $typedChampions->pluck('participant_id')->all());

            $typedChampions->each(function (TournamentChampion $champion) use ($participants): void {
                $champion->setRelation('resolvedParticipant', $participants->get($champion->participant_id));
            });
        }
    }
}
