<?php

namespace App\Http\Controllers;

use App\Http\Requests\Registrations\ImportRegistrationsRequest;
use App\Http\Requests\Registrations\RegistrationFilterRequest;
use App\Http\Requests\Registrations\StoreRegistrationRequest;
use App\Jobs\ImportTournamentRegistrations;
use App\Models\Tournament;
use App\Services\TournamentRegistrationExportService;
use App\Services\TournamentRegistrationImportService;
use App\Services\TournamentRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TournamentRegistrationController extends Controller
{
    public function __construct(
        private readonly TournamentRegistrationService $registrations,
        private readonly TournamentRegistrationImportService $imports,
        private readonly TournamentRegistrationExportService $exports,
    ) {}

    public function index(RegistrationFilterRequest $request, Tournament $tournament): View
    {
        $filters = $request->validated();
        $count = $this->registrations->count($tournament);

        return view('tournaments.registrations.index', [
            'tournament' => $tournament,
            'participants' => $this->registrations->paginate($tournament, $filters['search'] ?? null),
            'candidates' => $this->registrations->candidates($tournament, $filters['candidate_search'] ?? null),
            'registeredCount' => $count,
            'remainingSlots' => max(0, $tournament->max_participants - $count),
        ]);
    }

    public function store(StoreRegistrationRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->registrations->register($tournament, (int) $request->validated('participant_id'), $request->user());

        return back()->with('success', 'Participante inscrito correctamente.');
    }

    public function destroy(Request $request, Tournament $tournament, int $participant): RedirectResponse
    {
        Gate::authorize('manageRegistrations', $tournament);
        $this->registrations->remove($tournament, $participant, $request->user());

        return back()->with('success', 'Inscripción retirada correctamente.');
    }

    public function import(ImportRegistrationsRequest $request, Tournament $tournament): RedirectResponse
    {
        $file = $request->file('file');
        $threshold = config('matchpoint.registrations.queue_threshold_bytes');

        if ($file->getSize() > $threshold) {
            $path = $file->store('registration-imports');
            ImportTournamentRegistrations::dispatch($tournament->id, $request->user()->id, $path);

            return back()->with('success', 'Importación enviada a la cola de procesamiento.');
        }

        $result = $this->imports->import($tournament, $file->getRealPath(), $request->user());

        return back()->with('success', "Importación completada: {$result['imported']} registros agregados.")
            ->with('import_result', $result);
    }

    public function exportCsv(Tournament $tournament): StreamedResponse
    {
        Gate::authorize('viewRegistrations', $tournament);
        $filename = "{$tournament->slug}-inscripciones.csv";

        return response()->streamDownload(function () use ($tournament): void {
            $stream = fopen('php://output', 'wb');
            $this->exports->writeCsv($tournament, $stream);
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportXlsx(Tournament $tournament): BinaryFileResponse
    {
        Gate::authorize('viewRegistrations', $tournament);
        $path = $this->exports->createXlsx($tournament);

        return response()->download(
            $path,
            "{$tournament->slug}-inscripciones.xlsx",
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend();
    }
}
