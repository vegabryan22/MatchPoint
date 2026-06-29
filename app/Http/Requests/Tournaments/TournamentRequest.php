<?php

namespace App\Http\Requests\Tournaments;

use App\Enums\AcademicLevel;
use App\Enums\BestOf;
use App\Enums\GameType;
use App\Enums\ParticipantType;
use App\Enums\TournamentCapacity;
use App\Enums\TournamentFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'quick_registration_enabled' => ['sometimes', 'boolean'],
            'quick_registration_levels' => ['nullable', 'required_if:quick_registration_enabled,1', 'array', 'max:6'],
            'quick_registration_levels.*' => ['required', 'distinct', Rule::enum(AcademicLevel::class)],
            'quick_registration_notice' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('format') === TournamentFormat::WorldCup48->value
                && (int) $this->input('max_participants') !== TournamentCapacity::FortyEight->value) {
                $validator->errors()->add('max_participants', 'El formato Mundial 48 requiere exactamente 48 cupos.');
            }
        }];
    }
}
