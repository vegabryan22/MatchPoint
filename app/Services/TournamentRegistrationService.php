<?php

namespace App\Services;

use App\Enums\ParticipantType;
use App\Enums\RegistrationSource;
use App\Enums\TournamentStatus;
use App\Models\GameClub;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Repositories\Contracts\TournamentRegistrationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TournamentRegistrationService
{
    public function __construct(
        private readonly TournamentRegistrationRepositoryInterface $registrations,
        private readonly AuditService $audit,
    ) {}

    public function paginate(Tournament $tournament, ?string $search): LengthAwarePaginator
    {
        return $this->registrations->paginate($tournament, $search);
    }

    public function candidates(Tournament $tournament, ?string $search): Collection
    {
        return $this->registrations->candidates($tournament, $search);
    }

    public function count(Tournament $tournament): int
    {
        return $this->registrations->count($tournament);
    }

    public function isOpen(Tournament $tournament): bool
    {
        if (in_array($tournament->status, [TournamentStatus::Finished, TournamentStatus::Cancelled], true)) {
            return false;
        }

        if ($tournament->extraordinary_registration_enabled) {
            return true;
        }

        return $tournament->status === TournamentStatus::Registration
            && ! $tournament->draw()->exists()
            && ! $tournament->groups()->exists()
            && ! $tournament->registration_starts_at?->isFuture()
            && ! $tournament->registration_ends_at?->isPast();
    }

    public function register(
        Tournament $tournament,
        int $participantId,
        User $actor,
        RegistrationSource $source = RegistrationSource::Manual,
    ): void {
        DB::transaction(function () use ($tournament, $participantId, $actor, $source): void {
            $lockedTournament = Tournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();
            $this->ensureOpen($lockedTournament);
            $this->ensureParticipantIsActive($lockedTournament, $participantId);

            if ($this->registrations->isRegistered($lockedTournament, $participantId)) {
                throw ValidationException::withMessages(['participant_id' => 'El participante ya está inscrito.']);
            }

            if ($this->registrations->count($lockedTournament) >= $lockedTournament->max_participants) {
                throw ValidationException::withMessages(['participant_id' => 'El torneo alcanzó su capacidad máxima.']);
            }

            $this->registrations->register($lockedTournament, $participantId, $actor->id, $source);
            $this->audit->record('registration.created', $lockedTournament, [], [
                'participant_type' => $lockedTournament->participant_type->value,
                'participant_id' => $participantId,
                'source' => $source->value,
            ]);
        });
    }

    public function remove(Tournament $tournament, int $participantId, User $actor): void
    {
        DB::transaction(function () use ($tournament, $participantId, $actor): void {
            $lockedTournament = Tournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();
            $this->ensureOpen($lockedTournament);

            if (! $this->registrations->isRegistered($lockedTournament, $participantId)) {
                throw ValidationException::withMessages(['participant_id' => 'El participante no está inscrito.']);
            }

            $quickPlayer = $lockedTournament->participant_type === ParticipantType::Individual
                ? Player::query()->find($participantId)
                : null;
            $this->registrations->remove($lockedTournament, $participantId);
            if ($quickPlayer?->is_quick_entry && ! $quickPlayer->tournaments()->exists()) {
                $quickPlayer->delete();
            }
            $this->audit->record('registration.removed', $lockedTournament, [
                'participant_type' => $lockedTournament->participant_type->value,
                'participant_id' => $participantId,
            ], [], $actor->id);
        });
    }

    public function assignGameClub(Tournament $tournament, int $participantId, ?int $gameClubId, User $actor): void
    {
        DB::transaction(function () use ($tournament, $participantId, $gameClubId, $actor): void {
            $locked = Tournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();
            if (! $this->registrations->isRegistered($locked, $participantId)) {
                throw ValidationException::withMessages(['game_club_id' => 'El participante no está inscrito en este torneo.']);
            }
            $club = $gameClubId === null ? null : GameClub::query()->whereKey($gameClubId)->where('is_active', true)->first();
            if ($gameClubId !== null && ($club === null || ! $club->supportsGame($locked->game))) {
                throw ValidationException::withMessages(['game_club_id' => 'El equipo debe estar activo y corresponder al juego del torneo.']);
            }
            $this->registrations->assignGameClub($locked, $participantId, $gameClubId);
            $this->audit->record('registration.game_club_assigned', $locked, [], ['participant_id' => $participantId, 'game_club_id' => $gameClubId], $actor->id);
        });
    }

    public function setExtraordinaryRegistration(Tournament $tournament, bool $enabled, User $actor): void
    {
        if (in_array($tournament->status, [TournamentStatus::Finished, TournamentStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'registration' => 'No se pueden habilitar inscripciones en un torneo finalizado o cancelado.',
            ]);
        }

        $oldValue = $tournament->extraordinary_registration_enabled;
        $tournament->update(['extraordinary_registration_enabled' => $enabled]);
        $this->audit->record('registration.extraordinary_toggled', $tournament, ['enabled' => $oldValue], ['enabled' => $enabled], $actor->id);
    }

    private function ensureOpen(Tournament $tournament): void
    {
        if (in_array($tournament->status, [TournamentStatus::Finished, TournamentStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'registration' => 'El torneo está cerrado y no admite cambios en sus inscripciones.',
            ]);
        }

        if ($tournament->extraordinary_registration_enabled) {
            return;
        }

        if ($tournament->draw()->exists() || $tournament->groups()->exists()) {
            throw ValidationException::withMessages([
                'registration' => 'Las inscripciones están bloqueadas porque el sorteo ya fue generado.',
            ]);
        }

        if ($tournament->status !== TournamentStatus::Registration) {
            throw ValidationException::withMessages([
                'registration' => 'El torneo debe estar en estado Inscripciones.',
            ]);
        }

        if ($tournament->registration_starts_at?->isFuture()) {
            throw ValidationException::withMessages(['registration' => 'El periodo de inscripción todavía no inicia.']);
        }

        if ($tournament->registration_ends_at?->isPast()) {
            throw ValidationException::withMessages(['registration' => 'El periodo de inscripción ya finalizó.']);
        }
    }

    private function ensureParticipantIsActive(Tournament $tournament, int $participantId): void
    {
        $active = $tournament->participant_type === ParticipantType::Individual
            ? Player::query()->whereKey($participantId)->where('is_active', true)->exists()
            : Team::query()->whereKey($participantId)->where('is_active', true)->exists();

        if (! $active) {
            throw ValidationException::withMessages([
                'participant_id' => 'El participante no existe, está inactivo o no corresponde a la modalidad.',
            ]);
        }
    }
}
