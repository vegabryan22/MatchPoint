<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\TournamentRulesService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class TournamentRulesController extends Controller
{
    public function __construct(private readonly TournamentRulesService $rules) {}

    public function __invoke(Tournament $tournament): View
    {
        Gate::authorize('view', $tournament);

        return view('tournaments.rules.print', [
            'tournament' => $tournament,
            ...$this->rules->document($tournament),
        ]);
    }
}
