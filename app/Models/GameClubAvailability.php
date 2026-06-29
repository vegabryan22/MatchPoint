<?php

namespace App\Models;

use App\Enums\GameType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameClubAvailability extends Model
{
    public $timestamps = false;

    protected $fillable = ['game'];

    protected function casts(): array
    {
        return ['game' => GameType::class];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(GameClub::class, 'game_club_id');
    }
}
