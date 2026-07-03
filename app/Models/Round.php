<?php

namespace App\Models;

use App\Enums\BracketType;
use Database\Factories\RoundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    /** @use HasFactory<RoundFactory> */
    use HasFactory;

    protected $fillable = ['tournament_id', 'tournament_draw_id', 'name', 'number', 'bracket', 'starts_at'];

    protected function casts(): array
    {
        return ['bracket' => BracketType::class, 'starts_at' => 'datetime'];
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

    /** @return HasMany<GameMatch, $this> */
    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class)->orderBy('sequence');
    }
}
