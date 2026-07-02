<?php

namespace App\Http\Requests\Schedules;

use App\Enums\GamingPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTournamentStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageSchedule', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        $tournament = $this->route('tournament');
        $station = $this->route('station');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('tournament_stations')->where('tournament_id', $tournament->id)->ignore($station)],
            'platform' => ['required', Rule::enum(GamingPlatform::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after:available_from'],
        ];
    }
}
