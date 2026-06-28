<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MatchCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $matchId,
        public readonly ?int $actorId = null,
    ) {}
}
