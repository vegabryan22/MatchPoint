<?php

namespace App\Http\Requests\GameClubs;

final class UpdateGameClubRequest extends GameClubRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('game_club')) ?? false;
    }
}
