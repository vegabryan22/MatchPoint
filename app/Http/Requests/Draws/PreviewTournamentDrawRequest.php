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
            'manual_pairing' => ['nullable', 'boolean'],
            'generation_mode' => ['nullable', Rule::in(['replace', 'append', 'final'])],
            'batch_name' => ['nullable', 'string', 'max:80'],
            'selected_participants' => ['nullable', 'array', 'min:2'],
            'selected_participants.*' => ['required', 'integer', 'distinct'],
            'seeds' => ['nullable', 'array'],
            'seeds.*' => ['nullable', 'integer', 'min:1', 'max:128'],
        ];
    }
}
