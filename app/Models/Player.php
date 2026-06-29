<?php

namespace App\Models;

use App\Enums\ControllerType;
use App\Enums\PlayerLevel;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'nickname',
        'email',
        'photo_path',
        'country',
        'preferred_controller',
        'level',
        'is_active',
        'is_quick_entry',
    ];

    protected function casts(): array
    {
        return [
            'preferred_controller' => ControllerType::class,
            'level' => PlayerLevel::class,
            'is_active' => 'boolean',
            'is_quick_entry' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Team, $this> */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('is_captain')
            ->withTimestamps();
    }

    /** @return BelongsToMany<Tournament, $this> */
    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_players')
            ->withPivot(['registered_by', 'source', 'seed', 'registered_at', 'academic_level', 'controller_platform', 'controller_acknowledged_at', 'public_reference', 'game_club_id'])
            ->withTimestamps();
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path === null
            ? null
            : Storage::disk('public')->url($this->photo_path);
    }
}
