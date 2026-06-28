<?php

namespace App\Repositories\Contracts;

use App\Enums\ParticipantType;
use App\Models\GameMatch;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentDraw;

interface TournamentDrawRepositoryInterface
{
    public function createDraw(array $attributes): TournamentDraw;

    public function createRound(array $attributes): Round;

    public function createMatch(array $attributes): GameMatch;

    public function updateMatch(GameMatch $match, array $attributes): GameMatch;

    public function deleteArtifacts(Tournament $tournament): void;

    public function hasCompletedMatches(Tournament $tournament): bool;

    /** @return array<string, int> */
    public function encounterCounts(Tournament $tournament, ParticipantType $type, array $participantIds): array;

    public function updateSeeds(Tournament $tournament, array $orderedParticipantIds): void;

    public function clearSeeds(Tournament $tournament): void;
}
