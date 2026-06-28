<?php

namespace App\Repositories\Eloquent;

use App\Models\Tournament;
use App\Models\TournamentChampion;
use App\Repositories\Contracts\TournamentChampionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTournamentChampionRepository implements TournamentChampionRepositoryInterface
{
    public function findForTournament(Tournament $tournament): ?TournamentChampion
    {
        return TournamentChampion::query()->where('tournament_id', $tournament->id)->lockForUpdate()->first();
    }

    public function updateOrCreate(Tournament $tournament, array $attributes): TournamentChampion
    {
        return TournamentChampion::query()->updateOrCreate(
            ['tournament_id' => $tournament->id],
            $attributes,
        );
    }

    public function deleteForTournament(Tournament $tournament): void
    {
        TournamentChampion::query()->where('tournament_id', $tournament->id)->delete();
    }

    public function paginate(array $filters, int $perPage = 18): LengthAwarePaginator
    {
        return TournamentChampion::query()
            ->with(['tournament', 'decidingMatch'])
            ->when($filters['participant_type'] ?? null, fn ($query, $type) => $query->where('participant_type', $type))
            ->when($filters['game'] ?? null, fn ($query, $game) => $query->whereHas('tournament', fn ($query) => $query->where('game', $game)))
            ->when($filters['year'] ?? null, fn ($query, $year) => $query->whereYear('crowned_at', $year))
            ->orderByDesc('crowned_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function years(): array
    {
        return TournamentChampion::query()
            ->orderByDesc('crowned_at')
            ->get(['crowned_at'])
            ->pluck('crowned_at')
            ->map(fn ($date): int => $date->year)
            ->unique()
            ->values()
            ->all();
    }
}
