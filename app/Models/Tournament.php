<?php

namespace App\Models;

use App\Enums\BestOf;
use App\Enums\GameType;
use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'created_by',
        'name',
        'slug',
        'description',
        'game',
        'custom_game',
        'participant_type',
        'max_participants',
        'format',
        'best_of',
        'match_duration_minutes',
        'turnaround_minutes',
        'status',
        'registration_starts_at',
        'registration_ends_at',
        'starts_at',
        'ends_at',
        'quick_registration_enabled',
        'quick_registration_levels',
        'quick_registration_notice',
    ];

    protected function casts(): array
    {
        return [
            'game' => GameType::class,
            'participant_type' => ParticipantType::class,
            'format' => TournamentFormat::class,
            'best_of' => BestOf::class,
            'match_duration_minutes' => 'integer',
            'turnaround_minutes' => 'integer',
            'status' => TournamentStatus::class,
            'registration_starts_at' => 'datetime',
            'registration_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'quick_registration_enabled' => 'boolean',
            'quick_registration_levels' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_organizers')
            ->withPivot(['id', 'assigned_by', 'is_primary', 'assigned_at'])
            ->withTimestamps();
    }

    public function officials(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_officials')
            ->withPivot(['id', 'assigned_by', 'role', 'is_active', 'assigned_at'])
            ->withTimestamps();
    }

    public function stations(): HasMany
    {
        return $this->hasMany(TournamentStation::class)->orderBy('name');
    }

    /** @return BelongsToMany<Player, $this> */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'tournament_players')
            ->withPivot(['id', 'registered_by', 'source', 'seed', 'registered_at', 'academic_level', 'controller_platform', 'controller_acknowledged_at', 'public_reference', 'game_club_id'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Team, $this> */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'tournament_teams')
            ->withPivot(['id', 'registered_by', 'source', 'seed', 'registered_at', 'game_club_id'])
            ->withTimestamps();
    }

    /** @return HasMany<TournamentPlayer, $this> */
    public function playerRegistrations(): HasMany
    {
        return $this->hasMany(TournamentPlayer::class);
    }

    /** @return HasMany<TournamentTeam, $this> */
    public function teamRegistrations(): HasMany
    {
        return $this->hasMany(TournamentTeam::class);
    }

    /** @return HasOne<TournamentDraw, $this> */
    public function draw(): HasOne
    {
        return $this->hasOne(TournamentDraw::class);
    }

    /** @return HasMany<Round, $this> */
    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class)->orderBy('number');
    }

    /** @return HasMany<GameMatch, $this> */
    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    /** @return HasMany<TournamentGroup, $this> */
    public function groups(): HasMany
    {
        return $this->hasMany(TournamentGroup::class)->orderBy('position');
    }

    /** @return HasOne<TournamentChampion, $this> */
    public function champion(): HasOne
    {
        return $this->hasOne(TournamentChampion::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function gameLabel(): string
    {
        return $this->game === GameType::Other
            ? ($this->custom_game ?: $this->game->label())
            : $this->game->label();
    }
}
