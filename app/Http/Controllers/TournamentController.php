<?php

namespace App\Http\Controllers;

use App\Enums\BestOf;
use App\Enums\GameType;
use App\Enums\ParticipantType;
use App\Enums\TournamentCapacity;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Http\Requests\Tournaments\StoreTournamentRequest;
use App\Http\Requests\Tournaments\TournamentFilterRequest;
use App\Http\Requests\Tournaments\TransitionTournamentRequest;
use App\Http\Requests\Tournaments\UpdateTournamentRequest;
use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class TournamentController extends Controller
{
    public function __construct(private readonly TournamentService $tournaments) {}

    public function index(TournamentFilterRequest $request): View
    {
        return view('tournaments.index', [
            'tournaments' => $this->tournaments->paginate($request->validated()),
            ...$this->filterOptions(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Tournament::class);

        return view('tournaments.create', $this->formOptions());
    }

    public function store(StoreTournamentRequest $request): RedirectResponse
    {
        $tournament = $this->tournaments->create($request->validated(), $request->user());

        return redirect()->route('tournaments.show', $tournament)->with('success', 'Torneo creado como borrador.');
    }

    public function show(Tournament $tournament): View
    {
        Gate::authorize('view', $tournament);

        return view('tournaments.show', [
            'tournament' => $tournament->load('creator'),
            'transitions' => $this->tournaments->allowedTransitions($tournament),
        ]);
    }

    public function edit(Tournament $tournament): View
    {
        Gate::authorize('update', $tournament);

        return view('tournaments.edit', ['tournament' => $tournament, ...$this->formOptions()]);
    }

    public function update(UpdateTournamentRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->tournaments->update($tournament, $request->validated());

        return redirect()->route('tournaments.show', $tournament)->with('success', 'Torneo actualizado correctamente.');
    }

    public function duplicate(Tournament $tournament): RedirectResponse
    {
        Gate::authorize('duplicate', $tournament);
        $copy = $this->tournaments->duplicate($tournament, request()->user());

        return redirect()->route('tournaments.edit', $copy)->with('success', 'Torneo duplicado como borrador.');
    }

    public function transition(TransitionTournamentRequest $request, Tournament $tournament): RedirectResponse
    {
        $target = TournamentStatus::from($request->validated('status'));
        $this->tournaments->transition($tournament, $target);

        return back()->with('success', "Estado actualizado a {$target->label()}.");
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        Gate::authorize('delete', $tournament);
        $this->tournaments->delete($tournament);

        return redirect()->route('tournaments.index')->with('success', 'Torneo eliminado correctamente.');
    }

    private function formOptions(): array
    {
        return [
            'games' => GameType::cases(),
            'participantTypes' => ParticipantType::cases(),
            'capacities' => TournamentCapacity::cases(),
            'formats' => TournamentFormat::cases(),
            'bestOfOptions' => BestOf::cases(),
        ];
    }

    private function filterOptions(): array
    {
        return [
            'statuses' => TournamentStatus::cases(),
            'games' => GameType::cases(),
            'formats' => TournamentFormat::cases(),
            'participantTypes' => ParticipantType::cases(),
        ];
    }
}
