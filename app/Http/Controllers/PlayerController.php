<?php

namespace App\Http\Controllers;

use App\Enums\ControllerType;
use App\Enums\PlayerLevel;
use App\Http\Requests\Players\PlayerFilterRequest;
use App\Http\Requests\Players\StorePlayerRequest;
use App\Http\Requests\Players\TogglePlayerStatusRequest;
use App\Http\Requests\Players\UpdatePlayerRequest;
use App\Models\Player;
use App\Services\PlayerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class PlayerController extends Controller
{
    public function __construct(private readonly PlayerService $players) {}

    public function index(PlayerFilterRequest $request): View
    {
        return view('players.index', [
            'players' => $this->players->paginate($request->validated(), $request->user()),
            'countries' => $this->players->countries(),
            'levels' => PlayerLevel::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Player::class);

        return view('players.create', $this->formOptions());
    }

    public function store(StorePlayerRequest $request): RedirectResponse
    {
        $player = $this->players->create($request->validated(), $request->user());

        return redirect()->route('players.show', $player)->with('success', 'Jugador creado correctamente.');
    }

    public function show(Player $player): View
    {
        Gate::authorize('view', $player);

        return view('players.show', ['player' => $player]);
    }

    public function edit(Player $player): View
    {
        Gate::authorize('update', $player);

        return view('players.edit', ['player' => $player, ...$this->formOptions()]);
    }

    public function update(UpdatePlayerRequest $request, Player $player): RedirectResponse
    {
        $this->players->update($player, $request->validated());

        return redirect()->route('players.show', $player)->with('success', 'Jugador actualizado correctamente.');
    }

    public function toggleStatus(TogglePlayerStatusRequest $request, Player $player): RedirectResponse
    {
        $player = $this->players->toggleStatus($player);
        $message = $player->is_active ? 'Jugador activado.' : 'Jugador desactivado.';

        return back()->with('success', $message);
    }

    public function destroy(Player $player): RedirectResponse
    {
        Gate::authorize('delete', $player);
        $this->players->delete($player);

        return redirect()->route('players.index')->with('success', 'Jugador eliminado correctamente.');
    }

    private function formOptions(): array
    {
        return [
            'controllers' => ControllerType::cases(),
            'levels' => PlayerLevel::cases(),
        ];
    }
}
