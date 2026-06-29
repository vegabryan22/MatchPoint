<?php

namespace App\Http\Controllers;

use App\Enums\ParticipantType;
use App\Http\Requests\Statistics\StatisticsFilterRequest;
use App\Services\StatisticsService;
use Illuminate\View\View;

final class StatisticsController extends Controller
{
    public function __construct(private readonly StatisticsService $statistics) {}

    public function index(StatisticsFilterRequest $request): View
    {
        return view('statistics.index', $this->statistics->ranking($request->validated(), $request->user()));
    }

    public function show(StatisticsFilterRequest $request, ParticipantType $type, int $participant): View
    {
        return view('statistics.show', $this->statistics->participant($type, $participant, $request->validated(), $request->user()));
    }
}
