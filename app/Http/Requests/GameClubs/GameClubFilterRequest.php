<?php

namespace App\Http\Requests\GameClubs;

use App\Enums\GameClubType;
use App\Enums\GameType;
use App\Models\GameClub;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GameClubFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', GameClub::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'game' => ['nullable', Rule::enum(GameType::class)],
            'team_type' => ['nullable', Rule::enum(GameClubType::class)],
        ];
    }
}
