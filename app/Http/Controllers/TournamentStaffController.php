<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tournaments\AssignTournamentOfficialRequest;
use App\Http\Requests\Tournaments\AssignTournamentOrganizerRequest;
use App\Models\Tournament;
use App\Models\User;
use App\Services\TournamentStaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class TournamentStaffController extends Controller
{
    public function __construct(private readonly TournamentStaffService $staff) {}

    public function index(Request $request, Tournament $tournament): View
    {
        abort_unless($request->user()->can('manageOrganizers', $tournament) || $request->user()->can('manageOfficials', $tournament), 403);

        return view('tournaments.staff.index', $this->staff->details($tournament, $request->user()));
    }

    public function storeOrganizer(AssignTournamentOrganizerRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->staff->assignOrganizer($tournament, User::findOrFail($request->integer('user_id')), $request->user(), $request->boolean('is_primary'));

        return back()->with('success', 'Organizador asignado correctamente.');
    }

    public function destroyOrganizer(Request $request, Tournament $tournament, User $organizer): RedirectResponse
    {
        Gate::authorize('manageOrganizers', $tournament);
        $this->staff->removeOrganizer($tournament, $organizer, $request->user());

        return back()->with('success', 'Organizador retirado del torneo.');
    }

    public function storeOfficial(AssignTournamentOfficialRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->staff->assignReferee($tournament, User::findOrFail($request->integer('user_id')), $request->user());

        return back()->with('success', 'Árbitro asignado correctamente.');
    }

    public function destroyOfficial(Request $request, Tournament $tournament, User $official): RedirectResponse
    {
        Gate::authorize('manageOfficials', $tournament);
        $this->staff->removeReferee($tournament, $official, $request->user());

        return back()->with('success', 'Árbitro retirado del torneo.');
    }
}
