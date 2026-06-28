<?php

namespace App\Http\Requests\Statistics;

use App\Enums\GameType;
use App\Enums\ParticipantType;
use App\Models\TournamentChampion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChampionFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', TournamentChampion::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'participant_type' => ['nullable', Rule::enum(ParticipantType::class)],
            'game' => ['nullable', Rule::enum(GameType::class)],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ];
    }
}
