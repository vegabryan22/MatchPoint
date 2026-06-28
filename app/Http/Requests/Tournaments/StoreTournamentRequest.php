<?php

namespace App\Http\Requests\Tournaments;

use App\Models\Tournament;

final class StoreTournamentRequest extends TournamentRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Tournament::class) ?? false;
    }
}
