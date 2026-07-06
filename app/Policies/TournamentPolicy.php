<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Models\User;
use App\Services\TournamentAccessService;

final class TournamentPolicy
{
    public function __construct(private readonly TournamentAccessService $access) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Tournament $tournament): bool
    {
        return $this->access->canView($user, $tournament);
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdministrator() || $user->hasRole(RoleName::Organizer));
    }

    public function update(User $user, Tournament $tournament): bool
    {
        return $this->access->canManage($user, $tournament)
            && $tournament->status !== TournamentStatus::Finished;
    }

    public function delete(User $user, Tournament $tournament): bool
    {
        return $this->access->canManage($user, $tournament);
    }

    public function duplicate(User $user, Tournament $tournament): bool
    {
        return $this->access->canManage($user, $tournament);
    }

    public function viewRegistrations(User $user, Tournament $tournament): bool
    {
        return $this->access->canView($user, $tournament);
    }

    public function manageRegistrations(User $user, Tournament $tournament): bool
    {
        return $this->access->canManage($user, $tournament)
            && ! in_array($tournament->status, [TournamentStatus::Finished, TournamentStatus::Cancelled], true);
    }

    public function managePublicForms(User $user, Tournament $tournament): bool
    {
        return $this->access->canManage($user, $tournament)
            && ! in_array($tournament->status, [TournamentStatus::Finished, TournamentStatus::Cancelled], true);
    }

    public function viewDraw(User $user, Tournament $tournament): bool
    {
        return $this->access->canView($user, $tournament);
    }

    public function manageDraw(User $user, Tournament $tournament): bool
    {
        return $this->access->canManage($user, $tournament)
            && in_array($tournament->status, [TournamentStatus::Registration, TournamentStatus::InProgress], true);
    }

    public function manageMatches(User $user, Tournament $tournament): bool
    {
        return $this->access->canRecordMatches($user, $tournament);
    }

    public function viewSchedule(User $user, Tournament $tournament): bool
    {
        return $this->access->canView($user, $tournament);
    }

    public function manageSchedule(User $user, Tournament $tournament): bool
    {
        return $this->access->canManage($user, $tournament);
    }

    public function viewGroups(User $user, Tournament $tournament): bool
    {
        return $this->access->canView($user, $tournament);
    }

    public function manageGroups(User $user, Tournament $tournament): bool
    {
        return $this->access->canManage($user, $tournament);
    }

    public function manageOrganizers(User $user, Tournament $tournament): bool
    {
        return $this->access->canManageOrganizers($user);
    }

    public function manageOfficials(User $user, Tournament $tournament): bool
    {
        return $this->access->canManageOfficials($user, $tournament);
    }
}
