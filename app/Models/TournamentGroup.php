<?php

namespace App\Models;

use Database\Factories\TournamentGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentGroup extends Model
{
    /** @use HasFactory<TournamentGroupFactory> */
    use HasFactory;

    protected $table = 'groups';

    protected $fillable = ['tournament_id', 'name', 'position', 'qualifiers_count'];

    /** @return BelongsTo<Tournament, $this> */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /** @return HasMany<GroupParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(GroupParticipant::class, 'group_id')->orderBy('seed');
    }

    /** @return HasMany<GameMatch, $this> */
    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'group_id');
    }
}
