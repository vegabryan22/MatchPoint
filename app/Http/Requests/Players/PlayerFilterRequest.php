<?php

namespace App\Http\Requests\Players;

use App\Enums\PlayerLevel;
use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PlayerFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Player::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:100'],
            'level' => ['nullable', Rule::enum(PlayerLevel::class)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->filled('search') ? trim($this->string('search')->toString()) : null,
            'country' => $this->filled('country') ? $this->string('country')->toString() : null,
            'level' => $this->filled('level') ? $this->string('level')->toString() : null,
            'is_active' => $this->filled('is_active') ? $this->boolean('is_active') : null,
        ]);
    }
}
