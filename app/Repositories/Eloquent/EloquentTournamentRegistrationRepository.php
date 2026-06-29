<?php

namespace App\Repositories\Eloquent;

use App\Enums\ParticipantType;
use App\Enums\RegistrationSource;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Repositories\Contracts\TournamentRegistrationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class EloquentTournamentRegistrationRepository implements TournamentRegistrationRepositoryInterface
{
    public function count(Tournament $tournament): int
    {
        return $this->isIndividual($tournament)
            ? $tournament->players()->count()
            : $tournament->teams()->count();
    }

    public function paginate(Tournament $tournament, ?string $search, int $perPage = 15): LengthAwarePaginator
    {
        if ($this->isIndividual($tournament)) {
            return $tournament->players()
                ->when($search, fn ($query, $term) => $query->where(fn ($query) => $query->where('players.name', 'like', "%{$term}%")->orWhere('players.nickname', 'like', "%{$term}%")->orWhere('players.email', 'like', "%{$term}%")))
                ->orderBy('players.nickname')
                ->paginate($perPage)
                ->withQueryString();
        }

        return $tournament->teams()
            ->when($search, fn ($query, $term) => $query->where('teams.name', 'like', "%{$term}%"))
            ->orderBy('teams.name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function candidates(Tournament $tournament, ?string $search): Collection
    {
        if ($this->isIndividual($tournament)) {
            return Player::query()
                ->where('is_active', true)
                ->whereDoesntHave('tournaments', fn ($query) => $query->whereKey($tournament->id))
                ->when($search, fn ($query, $term) => $query->where(fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('nickname', 'like', "%{$term}%")))
                ->orderBy('nickname')
                ->limit(100)
                ->get();
        }

        return Team::query()
            ->where('is_active', true)
            ->whereDoesntHave('tournaments', fn ($query) => $query->whereKey($tournament->id))
            ->when($search, fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    public function all(Tournament $tournament): Collection
    {
        return $this->isIndividual($tournament)
            ? $tournament->players()->orderBy('players.nickname')->get()
            : $tournament->teams()->orderBy('teams.name')->get();
    }

    public function isRegistered(Tournament $tournament, int $participantId): bool
    {
        return $this->isIndividual($tournament)
            ? $tournament->players()->whereKey($participantId)->exists()
            : $tournament->teams()->whereKey($participantId)->exists();
    }

    public function register(Tournament $tournament, int $participantId, int $userId, RegistrationSource $source): void
    {
        $attributes = [
            'registered_by' => $userId,
            'source' => $source->value,
            'registered_at' => now(),
        ];

        if ($this->isIndividual($tournament)) {
            $tournament->players()->attach($participantId, $attributes);

            return;
        }

        $tournament->teams()->attach($participantId, $attributes);
    }

    public function remove(Tournament $tournament, int $participantId): void
    {
        if ($this->isIndividual($tournament)) {
            $tournament->players()->detach($participantId);

            return;
        }

        $tournament->teams()->detach($participantId);
    }

    public function assignGameClub(Tournament $tournament, int $participantId, ?int $gameClubId): void
    {
        $relation = $this->isIndividual($tournament) ? $tournament->players() : $tournament->teams();
        $relation->updateExistingPivot($participantId, ['game_club_id' => $gameClubId]);
    }

    private function isIndividual(Tournament $tournament): bool
    {
        return $tournament->participant_type === ParticipantType::Individual;
    }
}
