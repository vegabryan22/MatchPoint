<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\UpdateNotificationPreferencesRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', ['notifications' => $request->user()->notifications()->paginate(15), 'preferences' => $request->user()->notificationPreference()->firstOrCreate([])]);
    }

    public function update(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $request->user()->notificationPreference()->updateOrCreate([], $request->validated());

        return back()->with('success', 'Preferencias actualizadas.');
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back();
    }
}
