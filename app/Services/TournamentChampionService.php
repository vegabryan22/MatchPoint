<?php

namespace App\Services;

use App\Enums\BracketType;
use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Models\GameMatch;
use App\Models\TournamentChampion;
use App\Models\User;
use App\Repositories\Contracts\GameMatchRepositoryInterface;
use App\Repositories\Contracts\GroupStageRepositoryInterface;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use App\Repositories\Contracts\TournamentChampionRepositoryInterface;
use App\Repositories\Contracts\TournamentRegistrationRepositoryInterface;
use App\Services\Groups\StandingsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class TournamentChampionService
{
    public function __construct(
        private readonly TournamentChampionRepositoryInterface $champions,
        private readonly GameMatchRepositoryInterface $matches,
        private readonly StatisticsRepositoryInterface $statistics,
        private readonly GroupStageRepositoryInterface $groups,
        private readonly TournamentRegistrationRepositoryInterface $registrations,
        private readonly StandingsService $standings,
        private readonly AuditService $audit,
        private readonly TournamentAccessService $access,
    ) {}

    public function sync(int $matchId, ?int $actorId = null): void
    {
        DB::transaction(function () use ($matchId, $actorId): void {
            $match = $this->matches->findForUpdate($matchId);
            $match->load(['round', 'tournament']);

            if (! $this->isFinalRelated($match)) {
                return;
            }

            $winnerId = $this->decisiveWinner($match);
            $existing = $this->champions->findForTournament($match->tournament);

            if ($winnerId === null) {
                if ($existing !== null) {
                    $this->champions->deleteForTournament($match->tournament);
                    $this->audit->record('champion.revoked', $match->tournament, $existing->toArray(), [], $actorId);
                }

                return;
            }

            $attributes = [
                'participant_type' => $match->participant_type,
                'participant_id' => $winnerId,
                'deciding_match_id' => $match->id,
                'crowned_at' => $match->completed_at ?? now(),
            ];
            $champion = $this->champions->updateOrCreate($match->tournament, $attributes);
            $action = $existing === null ? 'champion.crowned' : 'champion.updated';
            $this->audit->record($action, $champion, $existing?->toArray() ?? [], $attributes, $actorId);
        });
    }

    public function paginate(array $filters, User $user): array
    {
        $filters['visible_tournament_ids'] = $this->access->visibleQuery($user)->pluck('id')->all();
        $champions = $this->champions->paginate($filters);
        $this->resolveParticipants($champions);

        return [
            'champions' => $champions,
            'years' => $this->champions->years($filters['visible_tournament_ids']),
            'filters' => $filters,
        ];
    }

    private function decisiveWinner(GameMatch $match): ?int
    {
        if ($match->status !== MatchStatus::Completed) {
            return null;
        }

        if (in_array($match->tournament->format, [TournamentFormat::RoundRobin, TournamentFormat::League], true)) {
            $group = $this->groups->groups($match->tournament)->first();
            if ($group === null) {
                return null;
            }
            $participants = $this->registrations->all($match->tournament)->keyBy('id');

            return $this->standings->calculate($group, $participants)->first()['participant_id'] ?? null;
        }

        if ($match->tournament->format === TournamentFormat::SingleElimination) {
            return $match->winner_id;
        }

        if ($match->round->number === 2) {
            return $match->winner_id;
        }

        $reset = GameMatch::query()
            ->where('tournament_id', $match->tournament_id)
            ->where('is_conditional', true)
            ->lockForUpdate()
            ->first();

        return $reset?->status === MatchStatus::Cancelled ? $match->winner_id : null;
    }

    private function isFinalRelated(GameMatch $match): bool
    {
        if (in_array($match->tournament->format, [TournamentFormat::RoundRobin, TournamentFormat::League], true)) {
            return $match->round?->bracket === BracketType::Group
                && ! $match->tournament->matches()->whereNotNull('group_id')->where('status', '!=', MatchStatus::Completed)->exists();
        }

        if ($match->tournament->format === TournamentFormat::SingleElimination) {
            return $match->round?->bracket === BracketType::Main
                && $match->winner_next_match_id === null;
        }

        return $match->tournament->format === TournamentFormat::DoubleElimination
            && $match->round?->bracket === BracketType::Finals;
    }

    private function resolveParticipants(LengthAwarePaginator $champions): void
    {
        $collection = $champions->getCollection();
        $maps = collect(ParticipantType::cases())->mapWithKeys(function (ParticipantType $type) use ($collection): array {
            $ids = $collection->where('participant_type', $type)->pluck('participant_id')->unique()->values()->all();

            return [$type->value => $this->statistics->participants($type, $ids)];
        });

        $collection->each(function (TournamentChampion $champion) use ($maps): void {
            $champion->setRelation('resolvedParticipant', $maps[$champion->participant_type->value]->get($champion->participant_id));
        });
    }
}
