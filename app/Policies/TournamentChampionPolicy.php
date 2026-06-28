<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\TournamentChampion;
use App\Models\User;

final class TournamentChampionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function manage(User $user, TournamentChampion $champion): bool
    {
        return $user->hasRole(RoleName::Administrator)
            || $user->hasRole(RoleName::Organizer);
    }
}
