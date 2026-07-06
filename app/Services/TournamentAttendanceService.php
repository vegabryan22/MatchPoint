<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ParticipantType;
use App\Enums\TournamentStatus;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
use App\Models\TournamentTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TournamentAttendanceService
{
    public function __construct(private readonly AuditService $audit) {}

    public function update(Tournament $tournament, int $participantId, AttendanceStatus $status, User $actor): void
    {
        DB::transaction(function () use ($tournament, $participantId, $status, $actor): void {
            $lockedTournament = Tournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();
            if (in_array($lockedTournament->status, [TournamentStatus::Finished, TournamentStatus::Cancelled], true)) {
                throw ValidationException::withMessages([
                    'attendance_status' => 'La asistencia no puede modificarse después de cerrar el torneo.',
                ]);
            }

            $registration = $this->registration($lockedTournament, $participantId);
            $before = [
                'attendance_status' => $registration->attendance_status->value,
                'checked_in_at' => $registration->checked_in_at,
                'checked_in_by' => $registration->checked_in_by,
            ];
            $registration->update([
                'attendance_status' => $status,
                'checked_in_at' => $status === AttendanceStatus::Pending ? null : now(),
                'checked_in_by' => $status === AttendanceStatus::Pending ? null : $actor->id,
            ]);
            $this->audit->record('registration.attendance_updated', $registration, $before, [
                'attendance_status' => $status->value,
                'checked_in_at' => $registration->checked_in_at,
                'checked_in_by' => $registration->checked_in_by,
            ], $actor->id);
        });
    }

    public function counts(Tournament $tournament): array
    {
        $query = $tournament->participant_type === ParticipantType::Individual
            ? $tournament->playerRegistrations()
            : $tournament->teamRegistrations();
        $counts = $query->selectRaw('attendance_status, count(*) as aggregate')
            ->groupBy('attendance_status')
            ->pluck('aggregate', 'attendance_status');

        return collect(AttendanceStatus::cases())->mapWithKeys(
            fn (AttendanceStatus $status): array => [$status->value => (int) ($counts[$status->value] ?? 0)],
        )->all();
    }

    public function confirmMatchParticipants(GameMatch $match, User $actor): void
    {
        $match->loadMissing('tournament');

        foreach ([$match->participant_a_id, $match->participant_b_id] as $participantId) {
            if ($participantId === null) {
                continue;
            }

            $registration = $this->registration($match->tournament, $participantId);
            if ($registration->attendance_status === AttendanceStatus::Present) {
                continue;
            }

            $previousStatus = $registration->attendance_status;
            $registration->update([
                'attendance_status' => AttendanceStatus::Present,
                'checked_in_at' => $match->completed_at ?? now(),
                'checked_in_by' => $actor->id,
            ]);
            $this->audit->record('registration.attendance_auto_confirmed', $registration, [
                'attendance_status' => $previousStatus->value,
            ], [
                'attendance_status' => AttendanceStatus::Present->value,
                'match_id' => $match->id,
            ], $actor->id);
        }
    }

    private function registration(Tournament $tournament, int $participantId): Model
    {
        $registration = $tournament->participant_type === ParticipantType::Individual
            ? TournamentPlayer::query()->where('tournament_id', $tournament->id)->where('player_id', $participantId)->lockForUpdate()->first()
            : TournamentTeam::query()->where('tournament_id', $tournament->id)->where('team_id', $participantId)->lockForUpdate()->first();

        if ($registration === null) {
            throw ValidationException::withMessages(['attendance_status' => 'El participante no está inscrito en este torneo.']);
        }

        return $registration;
    }
}
