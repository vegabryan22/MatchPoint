<?php

namespace App\Http\Requests\Tournaments;

use App\Enums\BestOf;
use App\Enums\GameType;
use App\Enums\ParticipantType;
use App\Enums\TournamentCapacity;
use App\Enums\TournamentFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class TournamentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'game' => ['required', Rule::enum(GameType::class)],
            'custom_game' => ['nullable', 'required_if:game,'.GameType::Other->value, 'string', 'max:120'],
            'participant_type' => ['required', Rule::enum(ParticipantType::class)],
            'max_participants' => ['required', Rule::in(array_column(TournamentCapacity::cases(), 'value'))],
            'format' => ['required', Rule::enum(TournamentFormat::class)],
            'best_of' => ['required', Rule::in(array_column(BestOf::cases(), 'value'))],
            'registration_starts_at' => ['nullable', 'required_with:registration_ends_at', 'date', 'before_or_equal:registration_ends_at'],
            'registration_ends_at' => ['nullable', 'required_with:registration_starts_at', 'date', 'after_or_equal:registration_starts_at', 'before_or_equal:starts_at'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }
}
