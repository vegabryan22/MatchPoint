<?php

namespace App\Http\Requests\GameClubs;

use App\Models\GameClub;

final class StoreGameClubRequest extends GameClubRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', GameClub::class) ?? false;
    }
}
