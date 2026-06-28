<?php

namespace App\Http\Requests\Draws;

use App\Enums\DrawMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewTournamentDrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageDraw', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', Rule::enum(DrawMethod::class)],
            'avoid_rematches' => ['required', 'boolean'],
            'seeds' => ['nullable', 'array'],
            'seeds.*' => ['nullable', 'integer', 'min:1', 'max:128'],
        ];
    }
}
