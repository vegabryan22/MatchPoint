<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Player;
use App\Models\User;
use App\Services\TournamentAccessService;

final class PlayerPolicy
{
    public function __construct(private readonly TournamentAccessService $access) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Player $player): bool
    {
        return $user->is_active && ($user->isAdministrator() || $player->user_id === $user->id || $player->managed_by === $user->id
            || $player->tournaments()->whereKey($this->access->visibleQuery($user)->pluck('id'))->exists());
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdministrator() || $user->hasRole(RoleName::Organizer));
    }

    public function update(User $user, Player $player): bool
    {
        return $user->isAdministrator() || ($user->hasRole(RoleName::Organizer) && ($player->managed_by === $user->id
            || $player->tournaments()->whereHas('organizers', fn ($query) => $query->whereKey($user->id))->exists()));
    }

    public function delete(User $user, Player $player): bool
    {
        return $this->update($user, $player);
    }
}
