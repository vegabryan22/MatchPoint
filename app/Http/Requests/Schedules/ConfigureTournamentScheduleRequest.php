<?php

namespace App\Http\Requests\Schedules;

use Illuminate\Foundation\Http\FormRequest;

final class ConfigureTournamentScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageSchedule', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return [
            'match_duration_minutes' => ['required', 'integer', 'between:5,180'],
            'turnaround_minutes' => ['required', 'integer', 'between:0,60'],
        ];
    }
}
