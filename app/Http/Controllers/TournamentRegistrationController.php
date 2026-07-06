<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Http\Requests\Registrations\AssignGameClubRequest;
use App\Http\Requests\Registrations\ImportRegistrationsRequest;
use App\Http\Requests\Registrations\RegistrationFilterRequest;
use App\Http\Requests\Registrations\StoreRegistrationRequest;
use App\Http\Requests\Registrations\UpdateAttendanceRequest;
use App\Jobs\ImportTournamentRegistrations;
use App\Models\GameClub;
use App\Models\Tournament;
use App\Services\TournamentAttendanceService;
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
        private readonly TournamentAttendanceService $attendance,
        private readonly TournamentRegistrationImportService $imports,
        private readonly TournamentRegistrationExportService $exports,
    ) {}

    public function index(RegistrationFilterRequest $request, Tournament $tournament): View
    {
        $filters = $request->validated();
        $count = $this->registrations->count($tournament);

        return view('tournaments.registrations.index', [
            'tournament' => $tournament,
            'participants' => $this->registrations->paginate(
                $tournament,
                $filters['search'] ?? null,
                isset($filters['attendance']) ? AttendanceStatus::from($filters['attendance']) : null,
            ),
            'candidates' => $this->registrations->candidates($tournament, $filters['candidate_search'] ?? null),
            'registeredCount' => $count,
            'remainingSlots' => max(0, $tournament->max_participants - $count),
            'registrationOpen' => $this->registrations->isOpen($tournament),
            'attendanceCounts' => $this->attendance->counts($tournament),
            'attendanceStatuses' => AttendanceStatus::cases(),
            'gameClubs' => GameClub::query()
                ->whereHas('availabilities', fn ($query) => $query->where('game', $tournament->game->value))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
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

    public function assignGameClub(AssignGameClubRequest $request, Tournament $tournament, int $participant): RedirectResponse
    {
        $clubId = $request->validated('game_club_id');
        $this->registrations->assignGameClub($tournament, $participant, $clubId === null ? null : (int) $clubId, $request->user());

        return back()->with('success', 'Equipo del videojuego actualizado.');
    }

    public function updateAttendance(UpdateAttendanceRequest $request, Tournament $tournament, int $participant): RedirectResponse
    {
        $status = AttendanceStatus::from($request->validated('attendance_status'));
        $this->attendance->update($tournament, $participant, $status, $request->user());

        return back()->with('success', "Asistencia actualizada a {$status->label()}.");
    }

    public function toggleExtraordinary(Request $request, Tournament $tournament): RedirectResponse
    {
        Gate::authorize('manageRegistrations', $tournament);
        $enabled = $request->boolean('enabled');
        $this->registrations->setExtraordinaryRegistration($tournament, $enabled, $request->user());

        return back()->with('success', $enabled
            ? 'Inscripciones extraordinarias habilitadas.'
            : 'Inscripciones extraordinarias cerradas.');
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
