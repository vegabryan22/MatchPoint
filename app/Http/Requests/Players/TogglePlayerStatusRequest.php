<?php

namespace App\Http\Requests\Players;

use Illuminate\Foundation\Http\FormRequest;

final class TogglePlayerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('player')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
