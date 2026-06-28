<?php

namespace App\Http\Requests\Teams;

final class UpdateTeamRequest extends TeamRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('team')) ?? false;
    }
}
