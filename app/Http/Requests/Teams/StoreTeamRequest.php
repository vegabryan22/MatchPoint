<?php

namespace App\Http\Requests\Teams;

use App\Models\Team;

final class StoreTeamRequest extends TeamRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Team::class) ?? false;
    }
}
