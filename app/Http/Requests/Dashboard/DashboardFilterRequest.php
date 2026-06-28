<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\ParticipantType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewDashboard') ?? false;
    }

    public function rules(): array
    {
        return [
            'participant_type' => ['nullable', Rule::enum(ParticipantType::class)],
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
