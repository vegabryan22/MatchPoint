<?php

namespace App\Http\Requests\GameClubs;

use App\Enums\GameType;
use App\Models\GameClub;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ImportPopularGameClubsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', GameClub::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'games' => ['required', 'array', 'min:1'],
            'games.*' => ['required', 'distinct', Rule::in([
                GameType::EaSportsFc->value,
                GameType::Fifa->value,
                GameType::Pes->value,
            ])],
            'catalogs' => ['required', 'array', 'min:1'],
            'catalogs.*' => ['required', 'distinct', Rule::in(['clubs', 'national_teams'])],
        ];
    }
}
