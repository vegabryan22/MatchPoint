<?php

namespace App\Http\Requests\Tournaments;

final class UpdateTournamentRequest extends TournamentRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('tournament')) ?? false;
    }
}
