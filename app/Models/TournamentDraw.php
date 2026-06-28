<?php

namespace App\Models;

use App\Enums\DrawMethod;
use Database\Factories\TournamentDrawFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentDraw extends Model
{
    /** @use HasFactory<TournamentDrawFactory> */
    use HasFactory;

    protected $fillable = [
        'tournament_id',
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
            'metadata' => 'array',
            'generated_at' => 'datetime',
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
}
