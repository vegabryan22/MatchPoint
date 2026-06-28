<?php

namespace App\Http\Requests\Teams;

use Illuminate\Foundation\Http\FormRequest;

final class ToggleTeamStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('team')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
