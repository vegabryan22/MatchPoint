<?php

namespace App\Http\Requests\Players;

use App\Enums\ControllerType;
use App\Enums\PlayerLevel;
use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

final class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Player::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'nickname' => ['required', 'string', 'max:80', 'unique:players,nickname'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:players,email'],
            'photo' => ['nullable', File::image()->max(2048)],
            'country' => ['required', 'string', 'max:100'],
            'preferred_controller' => ['required', Rule::enum(ControllerType::class)],
            'level' => ['required', Rule::enum(PlayerLevel::class)],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
