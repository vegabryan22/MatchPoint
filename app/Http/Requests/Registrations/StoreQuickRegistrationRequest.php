<?php

namespace App\Http\Requests\Registrations;

use App\Enums\PlayStationController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreQuickRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $levels = $this->route('tournament')->quick_registration_levels ?? [];

        return [
            'full_name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:80', 'unique:players,nickname'],
            'academic_level' => ['required', 'string', Rule::in($levels)],
            'controller_platform' => ['required', Rule::enum(PlayStationController::class)],
            'bring_own_controller' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => trim($this->string('full_name')->toString()),
            'username' => trim($this->string('username')->toString()),
            'academic_level' => trim($this->string('academic_level')->toString()),
        ]);
    }
}
