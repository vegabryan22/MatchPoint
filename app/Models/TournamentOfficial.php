<?php

namespace App\Models;

use App\Enums\TournamentOfficialRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentOfficial extends Model
{
    protected $fillable = ['tournament_id', 'user_id', 'assigned_by', 'role', 'is_active', 'assigned_at'];

    protected function casts(): array
    {
        return ['role' => TournamentOfficialRole::class, 'is_active' => 'boolean', 'assigned_at' => 'datetime'];
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
