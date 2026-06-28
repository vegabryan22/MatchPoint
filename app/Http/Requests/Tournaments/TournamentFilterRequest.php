<?php

namespace App\Http\Requests\Tournaments;

use App\Enums\GameType;
use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TournamentFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Tournament::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', Rule::enum(TournamentStatus::class)],
            'game' => ['nullable', Rule::enum(GameType::class)],
            'format' => ['nullable', Rule::enum(TournamentFormat::class)],
            'participant_type' => ['nullable', Rule::enum(ParticipantType::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['search', 'status', 'game', 'format', 'participant_type'] as $field) {
            $this->merge([$field => $this->filled($field) ? $this->string($field)->toString() : null]);
        }
    }
}
