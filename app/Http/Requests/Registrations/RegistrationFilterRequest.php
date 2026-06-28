<?php

namespace App\Http\Requests\Registrations;

use Illuminate\Foundation\Http\FormRequest;

final class RegistrationFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewRegistrations', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:160'],
            'candidate_search' => ['nullable', 'string', 'max:160'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['search', 'candidate_search'] as $field) {
            $this->merge([$field => $this->filled($field) ? trim($this->string($field)->toString()) : null]);
        }
    }
}
