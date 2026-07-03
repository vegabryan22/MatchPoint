<?php

namespace App\Models;

use App\Enums\DrawMethod;
use Database\Factories\TournamentDrawFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentDraw extends Model
{
    /** @use HasFactory<TournamentDrawFactory> */
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'batch_number',
        'name',
        'is_final_stage',
        'winner_id',
        'completed_at',
        'generated_by',
        'method',
        'avoid_rematches',
        'version',
        'metadata',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'method' => DrawMethod::class,
            'avoid_rematches' => 'boolean',
            'batch_number' => 'integer',
            'is_final_stage' => 'boolean',
            'metadata' => 'array',
            'generated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tournament, $this> */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class)->orderBy('number');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }
}
