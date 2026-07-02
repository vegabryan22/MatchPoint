<?php

namespace App\Models;

use App\Enums\GamingPlatform;
use Database\Factories\TournamentStationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentStation extends Model
{
    /** @use HasFactory<TournamentStationFactory> */
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'name',
        'platform',
        'location',
        'is_active',
        'available_from',
        'available_until',
    ];

    protected function casts(): array
    {
        return [
            'platform' => GamingPlatform::class,
            'is_active' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'tournament_station_id');
    }
}
