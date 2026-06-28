<?php

namespace App\Http\Controllers;

use App\Enums\DrawMethod;
use App\Http\Requests\Draws\GenerateTournamentDrawRequest;
use App\Http\Requests\Draws\PreviewTournamentDrawRequest;
use App\Models\Tournament;
use App\Services\TournamentDrawService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class TournamentDrawController extends Controller
{
    public function __construct(private readonly TournamentDrawService $draws) {}

    public function show(Tournament $tournament): View
    {
        Gate::authorize('viewDraw', $tournament);

        return view('tournaments.draws.show', $this->draws->details($tournament));
    }

    public function create(Tournament $tournament): View
    {
        Gate::authorize('manageDraw', $tournament);

        return view('tournaments.draws.create', [
            'tournament' => $tournament,
            'participants' => $this->draws->participants($tournament),
            'methods' => DrawMethod::cases(),
        ]);
    }

    public function preview(PreviewTournamentDrawRequest $request, Tournament $tournament): View
    {
        $plan = $this->draws->preview($tournament, $request->validated());

        return view('tournaments.draws.preview', ['tournament' => $tournament, 'plan' => $plan]);
    }

    public function store(GenerateTournamentDrawRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->draws->generate($tournament, $request->validated(), $request->user());

        return redirect()->route('tournaments.draws.show', $tournament)->with('success', 'Sorteo generado correctamente.');
    }

    public function destroy(Request $request, Tournament $tournament): RedirectResponse
    {
        Gate::authorize('manageDraw', $tournament);
        $this->draws->reset($tournament, $request->user());

        return redirect()->route('tournaments.draws.create', $tournament)->with('success', 'Sorteo eliminado; las inscripciones vuelven a estar desbloqueadas.');
    }
}
