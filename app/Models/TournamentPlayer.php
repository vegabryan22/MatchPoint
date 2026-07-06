<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
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
        'attendance_status',
        'checked_in_at',
        'checked_in_by',
        'academic_level',
        'controller_platform',
        'controller_acknowledged_at',
        'public_reference',
        'game_club_id',
    ];

    protected function casts(): array
    {
        return [
            'source' => RegistrationSource::class,
            'registered_at' => 'datetime',
            'attendance_status' => AttendanceStatus::class,
            'checked_in_at' => 'datetime',
            'controller_acknowledged_at' => 'datetime',
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

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function gameClub(): BelongsTo
    {
        return $this->belongsTo(GameClub::class);
    }
}
