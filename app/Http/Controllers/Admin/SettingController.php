<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function edit(): View
    {
        Gate::authorize('viewAny', Setting::class);

        return view('admin.settings.edit', [
            'settings' => $this->settings->grouped()->groupBy('group'),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->settings->updateMany($request->validated('settings'));

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
