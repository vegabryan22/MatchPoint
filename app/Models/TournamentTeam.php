<?php

namespace App\Models;

use App\Enums\RegistrationSource;
use Database\Factories\TournamentTeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentTeam extends Model
{
    /** @use HasFactory<TournamentTeamFactory> */
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'team_id',
        'registered_by',
        'source',
        'seed',
        'registered_at',
        'game_club_id',
    ];

    protected function casts(): array
    {
        return [
            'source' => RegistrationSource::class,
            'registered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tournament, $this> */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<User, $this> */
    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function gameClub(): BelongsTo
    {
        return $this->belongsTo(GameClub::class);
    }
}
