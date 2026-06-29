<?php

namespace App\Http\Controllers;

use App\Enums\PlayStationController;
use App\Http\Requests\Registrations\StoreQuickRegistrationRequest;
use App\Models\Tournament;
use App\Services\QuickRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class QuickRegistrationController extends Controller
{
    public function __construct(private readonly QuickRegistrationService $registrations) {}

    public function create(Tournament $tournament): View
    {
        return view('quick-registrations.create', [
            'tournament' => $tournament,
            'availability' => $this->registrations->availability($tournament),
            'controllers' => PlayStationController::cases(),
        ]);
    }

    public function store(StoreQuickRegistrationRequest $request, Tournament $tournament): RedirectResponse
    {
        $registration = $this->registrations->register($tournament, $request->validated());

        return redirect()->route('quick-registrations.confirmation', [$tournament, $registration->public_reference]);
    }

    public function confirmation(Tournament $tournament, string $reference): View
    {
        return view('quick-registrations.confirmation', [
            'registration' => $this->registrations->confirmation($tournament, $reference),
        ]);
    }
}
