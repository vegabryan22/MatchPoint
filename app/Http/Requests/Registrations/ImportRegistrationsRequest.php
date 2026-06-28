<?php

namespace App\Http\Requests\Registrations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

final class ImportRegistrationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageRegistrations', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return ['file' => ['required', File::types(['csv', 'txt'])->max(2048)]];
    }
}
