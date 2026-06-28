<?php

namespace App\Http\Requests\Tournaments;

use App\Enums\TournamentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TransitionTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(TournamentStatus::class)]];
    }
}
