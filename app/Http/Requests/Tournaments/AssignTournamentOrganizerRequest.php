<?php

namespace App\Http\Requests\Tournaments;

use Illuminate\Foundation\Http\FormRequest;

final class AssignTournamentOrganizerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageOrganizers', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return ['user_id' => ['required', 'integer', 'exists:users,id'], 'is_primary' => ['sometimes', 'boolean']];
    }
}
