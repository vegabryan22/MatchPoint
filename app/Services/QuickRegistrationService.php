<?php

namespace App\Services;

use App\Enums\ControllerType;
use App\Enums\ParticipantType;
use App\Enums\PlayerLevel;
use App\Enums\RegistrationSource;
use App\Enums\TournamentStatus;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class QuickRegistrationService
{
    public function __construct(private readonly AuditService $audit) {}

    public function availability(Tournament $tournament): array
    {
        $registered = $tournament->players()->count();
        $remaining = max(0, $tournament->max_participants - $registered);
        $message = match (true) {
            $tournament->status === TournamentStatus::Finished => 'Este torneo ya finalizó y no admite nuevas inscripciones.',
            $tournament->status === TournamentStatus::Cancelled => 'Este torneo fue cancelado y no admite inscripciones.',
            $tournament->participant_type !== ParticipantType::Individual => 'La inscripción rápida sólo está disponible para torneos individuales.',
            ! $tournament->quick_registration_enabled => 'La inscripción pública no está habilitada para este torneo.',
            empty($tournament->quick_registration_levels) => 'El organizador todavía no configuró los niveles habilitados.',
            ! $tournament->extraordinary_registration_enabled && $tournament->status !== TournamentStatus::Registration => 'El torneo no está recibiendo inscripciones.',
            ! $tournament->extraordinary_registration_enabled && $tournament->registration_starts_at?->isFuture() => 'El periodo de inscripción todavía no inicia.',
            ! $tournament->extraordinary_registration_enabled && $tournament->registration_ends_at?->isPast() => 'El periodo de inscripción ya finalizó.',
            ! $tournament->extraordinary_registration_enabled && ($tournament->draw()->exists() || $tournament->groups()->exists()) => 'Las inscripciones ya fueron cerradas por el organizador.',
            $remaining === 0 => 'Todos los cupos están ocupados.',
            default => null,
        };

        return ['open' => $message === null, 'message' => $message, 'registered' => $registered, 'remaining' => $remaining];
    }

    public function register(Tournament $tournament, array $data): TournamentPlayer
    {
        return DB::transaction(function () use ($tournament, $data): TournamentPlayer {
            $locked = Tournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();
            $availability = $this->availability($locked);

            if (! $availability['open']) {
                throw ValidationException::withMessages(['registration' => $availability['message']]);
            }

            if (Player::query()->where('nickname', $data['username'])->exists()) {
                throw ValidationException::withMessages(['username' => 'Ese nombre de usuario ya está registrado.']);
            }

            $player = Player::query()->create([
                'name' => $data['full_name'],
                'nickname' => $data['username'],
                'email' => null,
                'country' => null,
                'preferred_controller' => ControllerType::PlayStation,
                'level' => PlayerLevel::Beginner,
                'is_active' => true,
                'is_quick_entry' => true,
            ]);
            $registration = TournamentPlayer::query()->create([
                'tournament_id' => $locked->id,
                'player_id' => $player->id,
                'registered_by' => null,
                'source' => RegistrationSource::Public,
                'seed' => null,
                'registered_at' => now(),
                'academic_level' => $data['academic_level'],
                'controller_platform' => $data['controller_platform'],
                'controller_acknowledged_at' => now(),
                'public_reference' => $this->reference(),
            ]);

            $this->audit->record('registration.quick_created', $registration, [], [
                'tournament_id' => $locked->id,
                'nickname' => $player->nickname,
                'academic_level' => $registration->academic_level,
                'controller_platform' => $registration->controller_platform,
            ]);

            return $registration->load(['player', 'tournament']);
        });
    }

    public function confirmation(Tournament $tournament, string $reference): TournamentPlayer
    {
        return $tournament->playerRegistrations()
            ->where('public_reference', $reference)
            ->with(['player', 'tournament'])
            ->firstOrFail();
    }

    private function reference(): string
    {
        do {
            $reference = Str::upper(Str::random(10));
        } while (TournamentPlayer::query()->where('public_reference', $reference)->exists());

        return $reference;
    }
}
