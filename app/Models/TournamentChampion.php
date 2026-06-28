<?php

namespace App\Models;

use App\Enums\ParticipantType;
use Database\Factories\TournamentChampionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentChampion extends Model
{
    /** @use HasFactory<TournamentChampionFactory> */
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'participant_type',
        'participant_id',
        'deciding_match_id',
        'crowned_at',
    ];

    protected function casts(): array
    {
        return [
            'participant_type' => ParticipantType::class,
            'crowned_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tournament, $this> */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /** @return BelongsTo<GameMatch, $this> */
    public function decidingMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'deciding_match_id');
    }
}
