<?php

namespace App\Http\Requests\Schedules;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateTournamentScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageSchedule', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return ['starts_at' => ['nullable', 'date']];
    }
}
