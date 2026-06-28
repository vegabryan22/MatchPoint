<?php

namespace App\Models;

use Database\Factories\ScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    /** @use HasFactory<ScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'match_id',
        'game_number',
        'participant_a_score',
        'participant_b_score',
        'winner_id',
        'created_by',
    ];

    /** @return BelongsTo<GameMatch, $this> */
    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
