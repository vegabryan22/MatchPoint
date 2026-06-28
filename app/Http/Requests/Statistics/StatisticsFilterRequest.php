<?php

namespace App\Http\Requests\Statistics;

use App\Enums\GameType;
use App\Enums\ParticipantType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StatisticsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewStatistics') ?? false;
    }

    public function rules(): array
    {
        return [
            'participant_type' => ['nullable', Rule::enum(ParticipantType::class)],
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
            'game' => ['nullable', Rule::enum(GameType::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
