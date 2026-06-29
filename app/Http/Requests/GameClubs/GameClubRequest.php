<?php

namespace App\Http\Requests\GameClubs;

use App\Enums\GameClubType;
use App\Enums\GameType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

abstract class GameClubRequest extends FormRequest
{
    public function rules(): array
    {
        $club = $this->route('game_club');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('game_clubs')->where('team_type', $this->input('team_type'))->ignore($club)],
            'team_type' => ['required', Rule::enum(GameClubType::class)],
            'country_code' => ['nullable', 'required_if:team_type,'.GameClubType::NationalTeam->value, 'string', 'size:2', 'alpha:ascii'],
            'games' => ['required', 'array', 'min:1'],
            'games.*' => ['required', 'distinct', Rule::enum(GameType::class)],
            'crest' => ['nullable', File::image()->max(2048)],
            'crest_url' => ['nullable', 'url:http,https', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => filled($this->input('country_code')) ? mb_strtoupper($this->input('country_code')) : null,
        ]);
    }
}
