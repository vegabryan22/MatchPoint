<?php

namespace App\Http\Requests\Matches;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatchResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recordResult', $this->route('match')) ?? false;
    }

    public function rules(): array
    {
        return [
            'games' => ['required', 'array', 'min:1', 'max:5'],
            'games.*.participant_a_score' => ['nullable', 'required_with:games.*.participant_b_score', 'integer', 'min:0', 'max:99'],
            'games.*.participant_b_score' => ['nullable', 'required_with:games.*.participant_a_score', 'integer', 'min:0', 'max:99'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'games.*.participant_a_score' => 'marcador del participante A',
            'games.*.participant_b_score' => 'marcador del participante B',
            'duration_minutes' => 'duración',
            'observations' => 'observaciones',
        ];
    }
}
