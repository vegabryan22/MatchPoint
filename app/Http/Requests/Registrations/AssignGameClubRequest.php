<?php

namespace App\Http\Requests\Registrations;

use Illuminate\Foundation\Http\FormRequest;

final class AssignGameClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageRegistrations', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return ['game_club_id' => ['nullable', 'integer', 'exists:game_clubs,id']];
    }
}
