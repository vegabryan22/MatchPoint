<?php

namespace App\Http\Requests\PublicForms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PublicFormQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('managePublicForms', $this->route('tournament')) ?? false;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', Rule::in(['svg', 'png'])],
            'size' => ['required', 'integer', Rule::in([256, 512, 1024])],
            'download' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'format' => $this->query('format', 'svg'),
            'size' => $this->query('size', 512),
            'download' => $this->boolean('download'),
        ]);
    }
}
