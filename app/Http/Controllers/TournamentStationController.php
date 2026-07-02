<?php

namespace App\Http\Controllers;

use App\Http\Requests\Schedules\StoreTournamentStationRequest;
use App\Http\Requests\Schedules\UpdateTournamentStationRequest;
use App\Models\Tournament;
use App\Models\TournamentStation;
use App\Services\TournamentScheduleService;
use Illuminate\Http\RedirectResponse;

final class TournamentStationController extends Controller
{
    public function __construct(private readonly TournamentScheduleService $schedules) {}

    public function store(StoreTournamentStationRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->schedules->createStation($tournament, $request->validated());

        return back()->with('success', 'Consola agregada al torneo.');
    }

    public function update(UpdateTournamentStationRequest $request, Tournament $tournament, TournamentStation $station): RedirectResponse
    {
        $this->schedules->updateStation($tournament, $station, $request->validated());

        return back()->with('success', 'Consola actualizada.');
    }

    public function destroy(Tournament $tournament, TournamentStation $station): RedirectResponse
    {
        abort_unless(request()->user()->can('manageSchedule', $tournament), 403);
        $this->schedules->deleteStation($tournament, $station);

        return back()->with('success', 'Consola retirada del torneo.');
    }
}
