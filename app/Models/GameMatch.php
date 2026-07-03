<?php

namespace App\Models;

use App\Enums\BestOf;
use App\Enums\MatchSlot;
use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use Database\Factories\GameMatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    /** @use HasFactory<GameMatchFactory> */
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'tournament_draw_id',
        'round_id',
        'group_id',
        'sequence',
        'participant_type',
        'participant_a_id',
        'participant_b_id',
        'winner_id',
        'winner_next_match_id',
        'winner_next_slot',
        'loser_next_match_id',
        'loser_next_slot',
        'is_conditional',
        'status',
        'best_of',
        'scheduled_at',
        'tournament_station_id',
        'scheduled_end_at',
        'duration_seconds',
        'observations',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'participant_type' => ParticipantType::class,
            'status' => MatchStatus::class,
            'winner_next_slot' => MatchSlot::class,
            'loser_next_slot' => MatchSlot::class,
            'is_conditional' => 'boolean',
            'best_of' => BestOf::class,
            'scheduled_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tournament, $this> */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function draw(): BelongsTo
    {
        return $this->belongsTo(TournamentDraw::class, 'tournament_draw_id');
    }

    /** @return BelongsTo<Round, $this> */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    /** @return BelongsTo<TournamentGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(TournamentGroup::class, 'group_id');
    }

    /** @return BelongsTo<GameMatch, $this> */
    public function winnerNextMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'winner_next_match_id');
    }

    /** @return BelongsTo<GameMatch, $this> */
    public function loserNextMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'loser_next_match_id');
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(TournamentStation::class, 'tournament_station_id');
    }

    /** @return HasMany<Score, $this> */
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class, 'match_id')->orderBy('game_number');
    }

    public function loserId(): ?int
    {
        if ($this->participant_a_id === null || $this->participant_b_id === null || $this->winner_id === null) {
            return null;
        }

        return $this->winner_id === $this->participant_a_id
            ? $this->participant_b_id
            : $this->participant_a_id;
    }
}
