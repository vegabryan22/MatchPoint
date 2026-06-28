<?php

namespace App\Http\Controllers;

use App\Http\Requests\Groups\GenerateGroupStageRequest;
use App\Http\Requests\Groups\QualifyGroupStageRequest;
use App\Models\Tournament;
use App\Services\GroupStageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class TournamentGroupController extends Controller
{
    public function __construct(private readonly GroupStageService $groups) {}

    public function show(Tournament $tournament): View
    {
        Gate::authorize('viewGroups', $tournament);

        return view('tournaments.groups.show', $this->groups->details($tournament));
    }

    public function store(GenerateGroupStageRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->groups->generate($tournament, $request->validated(), $request->user());

        return redirect()->route('tournaments.groups.show', $tournament)->with('success', 'Grupos y calendario generados correctamente.');
    }

    public function qualify(QualifyGroupStageRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->groups->qualify($tournament, $request->user());

        return back()->with('success', 'Clasificados y fase eliminatoria generados.');
    }
}
