<?php

namespace App\Models;

use App\Enums\ParticipantType;
use Database\Factories\GroupParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupParticipant extends Model
{
    /** @use HasFactory<GroupParticipantFactory> */
    use HasFactory;

    protected $fillable = ['group_id', 'participant_type', 'participant_id', 'seed'];

    protected function casts(): array
    {
        return ['participant_type' => ParticipantType::class];
    }

    /** @return BelongsTo<TournamentGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(TournamentGroup::class, 'group_id');
    }
}
