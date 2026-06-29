<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentOrganizer extends Model
{
    protected $fillable = ['tournament_id', 'user_id', 'assigned_by', 'is_primary', 'assigned_at'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'assigned_at' => 'datetime'];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
