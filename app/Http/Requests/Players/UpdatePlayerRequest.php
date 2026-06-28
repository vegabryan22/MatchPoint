<?php

namespace App\Http\Requests\Players;

use App\Enums\ControllerType;
use App\Enums\PlayerLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

final class UpdatePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('player')) ?? false;
    }

    public function rules(): array
    {
        $player = $this->route('player');

        return [
            'name' => ['required', 'string', 'max:120'],
            'nickname' => ['required', 'string', 'max:80', Rule::unique('players')->ignore($player)],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('players')->ignore($player)],
            'photo' => ['nullable', File::image()->max(2048)],
            'country' => ['required', 'string', 'max:100'],
            'preferred_controller' => ['required', Rule::enum(ControllerType::class)],
            'level' => ['required', Rule::enum(PlayerLevel::class)],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
