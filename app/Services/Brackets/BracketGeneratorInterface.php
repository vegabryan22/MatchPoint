<?php

namespace App\Services\Brackets;

use App\Enums\TournamentFormat;
use App\Models\Tournament;

interface BracketGeneratorInterface
{
    public function supports(TournamentFormat $format): bool;

    public function build(Tournament $tournament, array $plan): BracketBlueprint;
}
