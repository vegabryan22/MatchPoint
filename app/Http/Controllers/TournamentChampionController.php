<?php

namespace App\Http\Controllers;

use App\Http\Requests\Statistics\ChampionFilterRequest;
use App\Services\TournamentChampionService;
use Illuminate\View\View;

final class TournamentChampionController extends Controller
{
    public function __construct(private readonly TournamentChampionService $champions) {}

    public function index(ChampionFilterRequest $request): View
    {
        return view('champions.index', $this->champions->paginate($request->validated()));
    }
}
