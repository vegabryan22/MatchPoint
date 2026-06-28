<?php

namespace App\Models;

use App\Enums\RegistrationSource;
use Database\Factories\TournamentPlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPlayer extends Model
{
    /** @use HasFactory<TournamentPlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'player_id',
        'registered_by',
        'source',
        'seed',
        'registered_at',
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

    /** @return BelongsTo<Player, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** @return BelongsTo<User, $this> */
    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
