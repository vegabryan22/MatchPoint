<?php

namespace App\Http\Controllers;

use App\Http\Requests\Matches\StoreMatchResultRequest;
use App\Http\Requests\Matches\StoreQuickMatchResultRequest;
use App\Http\Requests\Matches\UpdateMatchResultRequest;
use App\Models\GameMatch;
use App\Services\MatchResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class MatchResultController extends Controller
{
    public function __construct(private readonly MatchResultService $results) {}

    public function edit(GameMatch $match): View
    {
        Gate::authorize('recordResult', $match);

        return view('matches.results.edit', $this->results->details($match));
    }

    public function store(StoreMatchResultRequest $request, GameMatch $match): RedirectResponse|JsonResponse
    {
        $this->results->record($match, $request->validated(), $request->user());

        return $this->successResponse($request, $match, 'Resultado registrado y llave actualizada.');
    }

    public function quickStore(StoreQuickMatchResultRequest $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validated();
        $this->results->record($match, [
            'games' => [[
                'participant_a_score' => $data['score_a'],
                'participant_b_score' => $data['score_b'],
                'participant_a_penalties' => $data['penalties_a'] ?? null,
                'participant_b_penalties' => $data['penalties_b'] ?? null,
            ]],
        ], $request->user());

        return redirect()->route('tournaments.draws.show', [
            $match->tournament,
            'batch' => $match->tournament_draw_id,
        ])->with('success', 'Resultado guardado: '.$data['score_a'].'-'.$data['score_b']
            .(isset($data['penalties_a']) ? ' (penales '.$data['penalties_a'].'-'.$data['penalties_b'].')' : '').'.');
    }

    public function update(UpdateMatchResultRequest $request, GameMatch $match): RedirectResponse|JsonResponse
    {
        $this->results->correct($match, $request->validated(), $request->user());

        return $this->successResponse($request, $match, 'Resultado corregido y llave recalculada.');
    }

    private function successResponse(StoreMatchResultRequest $request, GameMatch $match, string $message): RedirectResponse|JsonResponse
    {
        $url = route('tournaments.draws.show', [
            $match->tournament,
            'batch' => $match->tournament_draw_id,
        ]);

        if ($request->expectsJson()) {
            $freshMatch = $match->fresh('scores');

            if (! $request->boolean('inline')) {
                $request->session()->flash('success', $message);
            }

            return response()->json([
                'message' => $message,
                'redirect' => $url,
                'match_id' => $freshMatch->id,
                'status' => $freshMatch->status->label(),
                'score_a' => $freshMatch->scores->sum('participant_a_score'),
                'score_b' => $freshMatch->scores->sum('participant_b_score'),
            ]);
        }

        return redirect($url)->with('success', $message);
    }
}
