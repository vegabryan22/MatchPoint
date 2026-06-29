<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Tournament;
use App\Models\User;

final class TournamentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Tournament $tournament): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $this->managesTournaments($user);
    }

    public function update(User $user, Tournament $tournament): bool
    {
        return $this->managesTournaments($user);
    }

    public function delete(User $user, Tournament $tournament): bool
    {
        return $this->managesTournaments($user);
    }

    public function duplicate(User $user, Tournament $tournament): bool
    {
        return $this->managesTournaments($user);
    }

    public function viewRegistrations(User $user, Tournament $tournament): bool
    {
        return $user->is_active;
    }

    public function manageRegistrations(User $user, Tournament $tournament): bool
    {
        return $this->managesTournaments($user);
    }

    public function managePublicForms(User $user, Tournament $tournament): bool
    {
        return $this->managesTournaments($user);
    }

    public function viewDraw(User $user, Tournament $tournament): bool
    {
        return $user->is_active;
    }

    public function manageDraw(User $user, Tournament $tournament): bool
    {
        return $this->managesTournaments($user);
    }

    public function manageMatches(User $user, Tournament $tournament): bool
    {
        return $this->managesTournaments($user)
            || $user->hasRole(RoleName::Referee);
    }

    public function viewGroups(User $user, Tournament $tournament): bool
    {
        return $user->is_active;
    }

    public function manageGroups(User $user, Tournament $tournament): bool
    {
        return $this->managesTournaments($user);
    }

    private function managesTournaments(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator)
            || $user->hasRole(RoleName::Organizer);
    }
}
