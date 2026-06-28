<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\DashboardFilterRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function __invoke(DashboardFilterRequest $request): View
    {
        return view('dashboard', $this->dashboard->summary($request->validated(), $request->user()));
    }

    public function data(DashboardFilterRequest $request): JsonResponse
    {
        $summary = $this->dashboard->summary($request->validated(), $request->user());

        return response()->json([
            'metrics' => $summary['metrics'],
            'live_html' => view('dashboard.live', $summary)->render(),
            'generated_at' => $summary['generatedAt']->toIso8601String(),
        ]);
    }
}
