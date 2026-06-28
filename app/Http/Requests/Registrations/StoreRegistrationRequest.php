<?php

namespace App\Http\Requests\Registrations;

use App\Enums\ParticipantType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageRegistrations', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        $table = $this->route('tournament')->participant_type === ParticipantType::Individual
            ? 'players'
            : 'teams';

        return [
            'participant_id' => [
                'required',
                'integer',
                Rule::exists($table, 'id')->where('is_active', true),
            ],
        ];
    }
}
