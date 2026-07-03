<?php

namespace App\Http\Controllers;

use App\Enums\DrawMethod;
use App\Http\Requests\Draws\GenerateTournamentDrawRequest;
use App\Http\Requests\Draws\PreviewTournamentDrawRequest;
use App\Models\Tournament;
use App\Services\TournamentDrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class TournamentDrawController extends Controller
{
    public function __construct(private readonly TournamentDrawService $draws) {}

    public function show(Request $request, Tournament $tournament): View
    {
        Gate::authorize('viewDraw', $tournament);

        return view('tournaments.draws.show', $this->draws->details($tournament, $request->integer('batch') ?: null));
    }

    public function live(Request $request, Tournament $tournament): JsonResponse
    {
        Gate::authorize('viewDraw', $tournament);
        $details = $this->draws->details($tournament, $request->integer('batch') ?: null);
        $html = view('tournaments.draws._world-sections', $details)->render();

        return response()->json(['version' => hash('sha256', $html), 'html' => $html]);
    }

    public function create(Request $request, Tournament $tournament): View
    {
        Gate::authorize('manageDraw', $tournament);

        $tournament->loadMissing('draws');
        $mode = in_array($request->query('mode'), ['append', 'final'], true) ? $request->query('mode') : 'replace';
        $usedParticipantIds = $tournament->draws->flatMap(fn ($draw) => $draw->metadata['active_participant_ids'] ?? [])->map(fn ($id): int => (int) $id)->unique();
        $winnerIds = $tournament->draws->where('is_final_stage', false)->pluck('winner_id')->filter()->map(fn ($id): int => (int) $id);

        return view('tournaments.draws.create', [
            'tournament' => $tournament,
            'participants' => $this->draws->participants($tournament),
            'methods' => DrawMethod::cases(),
            'generationMode' => $mode,
            'activeParticipantIds' => $mode === 'final' ? $winnerIds : collect(),
            'usedParticipantIds' => $usedParticipantIds,
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
        $this->draws->reset($tournament, $request->user(), $request->integer('batch') ?: null);

        return redirect()->route('tournaments.draws.show', $tournament)->with('success', 'Tanda eliminada correctamente.');
    }
}
