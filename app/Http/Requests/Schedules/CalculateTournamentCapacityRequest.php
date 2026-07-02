<?php

namespace App\Http\Requests\Schedules;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CalculateTournamentCapacityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewSchedule', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return [
            'target_hours' => ['nullable', 'integer', 'between:0,168'],
            'target_minutes' => ['nullable', 'integer', 'between:0,59'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->hasAny(['target_hours', 'target_minutes']) && $this->targetDurationMinutes() < 1) {
                $validator->errors()->add('target_hours', 'La duración objetivo debe ser mayor que cero.');
            }
        }];
    }

    public function targetDurationMinutes(): ?int
    {
        if (! $this->hasAny(['target_hours', 'target_minutes'])) {
            return null;
        }

        return ((int) $this->input('target_hours', 0) * 60) + (int) $this->input('target_minutes', 0);
    }
}
