<?php

namespace App\Http\Requests\Matches;

use Illuminate\Foundation\Http\FormRequest;

final class StoreQuickMatchResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recordResult', $this->route('match')) ?? false;
    }

    public function rules(): array
    {
        return [
            'score_a' => ['required', 'integer', 'min:0', 'max:99'],
            'score_b' => ['required', 'integer', 'min:0', 'max:99'],
            'penalties_a' => ['nullable', 'required_with:penalties_b', 'integer', 'min:0', 'max:99'],
            'penalties_b' => ['nullable', 'required_with:penalties_a', 'integer', 'min:0', 'max:99'],
            'match_id' => ['required', 'integer'],
            'batch' => ['nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'score_a' => 'marcador del jugador A',
            'score_b' => 'marcador del jugador B',
            'penalties_a' => 'penales del jugador A',
            'penalties_b' => 'penales del jugador B',
        ];
    }
}
