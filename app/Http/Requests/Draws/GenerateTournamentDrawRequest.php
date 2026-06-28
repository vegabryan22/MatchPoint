<?php

namespace App\Http\Requests\Draws;

final class GenerateTournamentDrawRequest extends PreviewTournamentDrawRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'resolved_order' => ['required', 'array', 'min:2'],
            'resolved_order.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
