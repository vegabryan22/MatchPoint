<?php

namespace App\Http\Controllers;

use App\Enums\GameClubType;
use App\Enums\GameType;
use App\Http\Requests\GameClubs\GameClubFilterRequest;
use App\Http\Requests\GameClubs\ImportPopularGameClubsRequest;
use App\Http\Requests\GameClubs\StoreGameClubRequest;
use App\Http\Requests\GameClubs\UpdateGameClubRequest;
use App\Models\GameClub;
use App\Services\GameClubService;
use App\Services\TheSportsDbClubImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class GameClubController extends Controller
{
    public function __construct(private readonly GameClubService $clubs, private readonly TheSportsDbClubImportService $imports) {}

    public function index(GameClubFilterRequest $request): View
    {
        return view('game-clubs.index', [
            'clubs' => $this->clubs->paginate($request->validated()),
            'games' => GameType::cases(),
            'types' => GameClubType::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', GameClub::class);

        return view('game-clubs.create', ['games' => GameType::cases(), 'types' => GameClubType::cases()]);
    }

    public function store(StoreGameClubRequest $request): RedirectResponse
    {
        $this->clubs->create($request->validated());

        return redirect()->route('game-clubs.index')->with('success', 'Club creado correctamente.');
    }

    public function edit(GameClub $gameClub): View
    {
        Gate::authorize('update', $gameClub);

        return view('game-clubs.edit', [
            'club' => $gameClub->load('availabilities'),
            'games' => GameType::cases(),
            'types' => GameClubType::cases(),
        ]);
    }

    public function update(UpdateGameClubRequest $request, GameClub $gameClub): RedirectResponse
    {
        $this->clubs->update($gameClub, $request->validated());

        return redirect()->route('game-clubs.index')->with('success', 'Club actualizado.');
    }

    public function destroy(GameClub $gameClub): RedirectResponse
    {
        Gate::authorize('delete', $gameClub);
        $this->clubs->delete($gameClub);

        return back()->with('success', 'Club eliminado.');
    }

    public function importPopular(ImportPopularGameClubsRequest $request): RedirectResponse
    {
        $result = $this->imports->importPopular($request->validated('games'), $request->validated('catalogs'));

        return back()->with('success', "Catálogo actualizado: {$result['imported']} equipos cargados y {$result['failed']} omitidos.");
    }
}
