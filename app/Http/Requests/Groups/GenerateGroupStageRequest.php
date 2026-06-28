<?php

namespace App\Http\Requests\Groups;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateGroupStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageGroups', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return [
            'group_count' => ['required', 'integer', 'min:1', 'max:16'],
            'qualifiers_per_group' => ['required', 'integer', 'min:0', 'max:8'],
        ];
    }
}
