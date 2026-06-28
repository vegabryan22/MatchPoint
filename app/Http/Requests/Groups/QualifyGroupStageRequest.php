<?php

namespace App\Http\Requests\Groups;

use Illuminate\Foundation\Http\FormRequest;

final class QualifyGroupStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageGroups', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
