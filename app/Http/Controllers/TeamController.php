<?php

namespace App\Http\Controllers;

use App\Http\Requests\Teams\StoreTeamRequest;
use App\Http\Requests\Teams\TeamFilterRequest;
use App\Http\Requests\Teams\ToggleTeamStatusRequest;
use App\Http\Requests\Teams\UpdateTeamRequest;
use App\Models\Team;
use App\Services\PlayerService;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class TeamController extends Controller
{
    public function __construct(
        private readonly TeamService $teams,
        private readonly PlayerService $players,
    ) {}

    public function index(TeamFilterRequest $request): View
    {
        return view('teams.index', ['teams' => $this->teams->paginate($request->validated())]);
    }

    public function create(): View
    {
        Gate::authorize('create', Team::class);

        return view('teams.create', ['players' => $this->players->forTeamSelection()]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $team = $this->teams->create($request->validated());

        return redirect()->route('teams.show', $team)->with('success', 'Equipo creado correctamente.');
    }

    public function show(Team $team): View
    {
        Gate::authorize('view', $team);

        return view('teams.show', ['team' => $this->teams->details($team)]);
    }

    public function edit(Team $team): View
    {
        Gate::authorize('update', $team);

        return view('teams.edit', [
            'team' => $this->teams->details($team),
            'players' => $this->players->forTeamSelection($team),
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->teams->update($team, $request->validated());

        return redirect()->route('teams.show', $team)->with('success', 'Equipo actualizado correctamente.');
    }

    public function toggleStatus(ToggleTeamStatusRequest $request, Team $team): RedirectResponse
    {
        $team = $this->teams->toggleStatus($team);

        return back()->with('success', $team->is_active ? 'Equipo activado.' : 'Equipo desactivado.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        Gate::authorize('delete', $team);
        $this->teams->delete($team);

        return redirect()->route('teams.index')->with('success', 'Equipo eliminado correctamente.');
    }
}
