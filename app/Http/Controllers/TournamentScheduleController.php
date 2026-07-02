<?php

namespace App\Http\Controllers;

use App\Enums\GamingPlatform;
use App\Http\Requests\Schedules\CalculateTournamentCapacityRequest;
use App\Http\Requests\Schedules\ConfigureTournamentScheduleRequest;
use App\Http\Requests\Schedules\GenerateTournamentScheduleRequest;
use App\Models\Tournament;
use App\Services\TournamentScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class TournamentScheduleController extends Controller
{
    public function __construct(private readonly TournamentScheduleService $schedules) {}

    public function index(CalculateTournamentCapacityRequest $request, Tournament $tournament): View
    {
        return view('tournaments.schedule.index', [
            'tournament' => $tournament,
            'platforms' => GamingPlatform::cases(),
            ...$this->schedules->overview($tournament),
            'capacity' => $this->schedules->capacityAnalysis($tournament, $request->targetDurationMinutes()),
        ]);
    }

    public function configure(ConfigureTournamentScheduleRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->schedules->configure($tournament, $request->validated());

        return back()->with('success', 'Duración y tiempo de preparación actualizados.');
    }

    public function generate(GenerateTournamentScheduleRequest $request, Tournament $tournament): RedirectResponse
    {
        $result = $this->schedules->generate($tournament, $request->validated('starts_at'));

        return back()->with('success', "Horario generado para {$result['matches']} partidos. Final estimada: {$result['ends_at']->format('d/m/Y H:i')}.");
    }

    public function clear(Tournament $tournament): RedirectResponse
    {
        Gate::authorize('manageSchedule', $tournament);
        $this->schedules->clear($tournament);

        return back()->with('success', 'Programación pendiente eliminada.');
    }
}
