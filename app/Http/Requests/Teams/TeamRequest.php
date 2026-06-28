<?php

namespace App\Http\Requests\Teams;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

abstract class TeamRequest extends FormRequest
{
    public function rules(): array
    {
        $team = $this->route('team');
        $rawPlayerIds = $this->input('player_ids', []);
        $playerIds = is_array($rawPlayerIds) ? array_map('intval', $rawPlayerIds) : [];

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('teams')->ignore($team)],
            'logo' => ['nullable', File::image()->max(2048)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'player_ids' => ['nullable', 'array'],
            'player_ids.*' => ['integer', 'distinct', 'exists:players,id'],
            'captain_id' => ['nullable', 'integer', Rule::in($playerIds)],
        ];
    }
}
