<?php

namespace Database\Seeders;

use App\Enums\BestOf;
use App\Enums\ControllerType;
use App\Enums\DrawMethod;
use App\Enums\GameClubType;
use App\Enums\GameType;
use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Enums\PlayerLevel;
use App\Enums\RegistrationSource;
use App\Enums\RoleName;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\GameClub;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Role;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\GroupStageService;
use App\Services\MatchResultService;
use App\Services\TournamentDrawService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoSeeder extends Seeder
{
    private const COMPLETED_SLUG = 'copa-matchpoint-2026';

    private const GROUPS_SLUG = 'liga-esports-san-jose';

    private const REGISTRATION_SLUG = 'clasificatorio-fc-costa-rica';

    public function __construct(
        private readonly TournamentDrawService $draws,
        private readonly GroupStageService $groups,
        private readonly MatchResultService $results,
    ) {}

    public function run(): void
    {
        $this->call([RoleSeeder::class, SettingSeeder::class, AdminUserSeeder::class]);
        $gameTeams = $this->createDemoGameTeams();

        if (Tournament::query()->where('slug', self::COMPLETED_SLUG)->exists()) {
            Tournament::query()->where('slug', self::REGISTRATION_SLUG)->update([
                'quick_registration_enabled' => true,
                'quick_registration_levels' => json_encode(['7', '8', '9', '10', '11', '12']),
                'quick_registration_notice' => 'Debes llevar tu propio control PS4 o PS5, cargado y en buen estado.',
            ]);
            $this->assignGameClubs($gameTeams);
            $this->command?->warn('Los datos de demostración ya existen; no se generaron duplicados.');

            return;
        }

        $organizer = $this->createUser(
            'Organizador Demo',
            'organizer@example.com',
            'DemoOrganizador!123',
            RoleName::Organizer,
        );
        $referee = $this->createUser(
            'Árbitro Demo',
            'referee@example.com',
            'DemoArbitro!123',
            RoleName::Referee,
        );
        $players = $this->createPlayers();

        $this->createTeams($players);
        $this->createCompletedTournament($players->take(8)->all(), $organizer, $referee);
        $this->createGroupsTournament($players->slice(4, 8)->values()->all(), $organizer, $referee);
        $this->createRegistrationTournament($players->take(6)->all(), $organizer);
        $this->assignGameClubs($gameTeams);

        $this->command?->info('Datos de demostración creados correctamente.');
    }

    private function createUser(
        string $name,
        string $email,
        string $password,
        RoleName $roleName,
    ): User {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );
        $role = Role::query()->where('slug', $roleName->value)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    /** @return Collection<int, Player> */
    private function createPlayers(): Collection
    {
        $players = [
            ['Carlos Mora', 'TicoGol', 'Costa Rica', ControllerType::PlayStation, PlayerLevel::Professional],
            ['Sofía Jiménez', 'SofiSkill', 'Costa Rica', ControllerType::Xbox, PlayerLevel::Advanced],
            ['Daniel Rojas', 'DaniCR7', 'Costa Rica', ControllerType::PlayStation, PlayerLevel::Professional],
            ['Valeria Solano', 'ValePress', 'Costa Rica', ControllerType::PlayStation, PlayerLevel::Advanced],
            ['Mateo Vargas', 'MVPuraVida', 'Costa Rica', ControllerType::Xbox, PlayerLevel::Intermediate],
            ['Camila Herrera', 'CamiTiki', 'Panamá', ControllerType::PlayStation, PlayerLevel::Advanced],
            ['Andrés Castillo', 'AndyGol', 'Guatemala', ControllerType::Xbox, PlayerLevel::Professional],
            ['Lucía Méndez', 'Luci10', 'El Salvador', ControllerType::PlayStation, PlayerLevel::Intermediate],
            ['Sebastián Cruz', 'SebaFC', 'Honduras', ControllerType::PlayStation, PlayerLevel::Advanced],
            ['Natalia Vega', 'NatyRush', 'Nicaragua', ControllerType::Xbox, PlayerLevel::Professional],
            ['Diego Ramírez', 'DiegoPrime', 'México', ControllerType::PlayStation, PlayerLevel::Advanced],
            ['Isabella Torres', 'IsaGol', 'Colombia', ControllerType::PlayStation, PlayerLevel::Professional],
            ['Gabriel Flores', 'GaboPlay', 'Costa Rica', ControllerType::Keyboard, PlayerLevel::Intermediate],
            ['Mariana López', 'MariElite', 'Costa Rica', ControllerType::Xbox, PlayerLevel::Advanced],
            ['Emilio Sánchez', 'EmiControl', 'Panamá', ControllerType::PlayStation, PlayerLevel::Beginner],
            ['Paula Navarro', 'PauChampion', 'Guatemala', ControllerType::Xbox, PlayerLevel::Professional],
        ];

        return collect($players)->map(function (array $data, int $index): Player {
            return Player::query()->updateOrCreate(
                ['email' => sprintf('player%02d@example.com', $index + 1)],
                [
                    'name' => $data[0],
                    'nickname' => $data[1],
                    'country' => $data[2],
                    'preferred_controller' => $data[3],
                    'level' => $data[4],
                    'is_active' => true,
                ],
            );
        });
    }

    /** @param Collection<int, Player> $players */
    private function createTeams(Collection $players): void
    {
        $teams = [
            ['Pura Vida Gaming', 'Talento competitivo costarricense.'],
            ['Central Storm', 'Escuadra centroamericana de alto rendimiento.'],
            ['Ticos Elite', 'Equipo especializado en EA Sports FC.'],
            ['Neon Jaguars', 'Competición, estrategia y juego limpio.'],
        ];

        foreach ($teams as $index => [$name, $description]) {
            $team = Team::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $description, 'is_active' => true],
            );
            $members = $players->slice($index * 4, 4)->values();
            $team->players()->sync($members->mapWithKeys(
                fn (Player $player, int $memberIndex): array => [
                    $player->id => ['is_captain' => $memberIndex === 0],
                ],
            )->all());
        }
    }

    /** @param array<int, Player> $players */
    private function createCompletedTournament(array $players, User $organizer, User $referee): void
    {
        $tournament = $this->createTournament([
            'name' => 'Copa MatchPoint 2026',
            'slug' => self::COMPLETED_SLUG,
            'description' => 'Torneo demo finalizado con llave completa y campeón histórico.',
            'max_participants' => 8,
            'format' => TournamentFormat::SingleElimination,
            'status' => TournamentStatus::Registration,
            'registration_starts_at' => now()->subMonths(2),
            'registration_ends_at' => now()->subMonth()->subDays(2),
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subMonth()->addDay(),
        ], $organizer);
        $this->registerPlayers($tournament, $players, $organizer);
        $this->draws->generate($tournament, [
            'method' => DrawMethod::Manual->value,
            'resolved_order' => collect($players)->pluck('id')->all(),
            'avoid_rematches' => false,
        ], $organizer);
        $tournament->update(['status' => TournamentStatus::InProgress]);

        $safety = 0;
        while ($safety++ < 10) {
            $match = GameMatch::query()
                ->where('tournament_id', $tournament->id)
                ->where('status', MatchStatus::Pending)
                ->whereNotNull('participant_a_id')
                ->whereNotNull('participant_b_id')
                ->orderBy('round_id')
                ->orderBy('sequence')
                ->first();

            if ($match === null) {
                break;
            }

            $this->results->record($match, [
                'games' => [['participant_a_score' => 3, 'participant_b_score' => 1]],
                'duration_minutes' => 14,
                'observations' => 'Resultado generado para la demostración funcional.',
            ], $referee);
        }

        if (! $tournament->champion()->exists()) {
            throw new RuntimeException('No fue posible generar el campeón del torneo demo.');
        }

        $tournament->matches()->update(['scheduled_at' => now()->subMonth()]);
        $tournament->update(['status' => TournamentStatus::Finished]);
    }

    /** @param array<int, Player> $players */
    private function createGroupsTournament(array $players, User $organizer, User $referee): void
    {
        $tournament = $this->createTournament([
            'name' => 'Liga Esports San José',
            'slug' => self::GROUPS_SLUG,
            'description' => 'Fase de grupos demo en curso con posiciones y próximos partidos.',
            'max_participants' => 8,
            'format' => TournamentFormat::GroupsKnockout,
            'status' => TournamentStatus::Registration,
            'registration_starts_at' => now()->subWeeks(3),
            'registration_ends_at' => now()->subWeek(),
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addWeek(),
        ], $organizer);
        $this->registerPlayers($tournament, $players, $organizer);
        $this->groups->generate($tournament, [
            'group_count' => 2,
            'qualifiers_per_group' => 2,
        ], $organizer);
        $tournament->update(['status' => TournamentStatus::InProgress]);

        $matches = $tournament->matches()->whereNotNull('group_id')->orderBy('round_id')->orderBy('sequence')->get();
        foreach ($matches->take(6) as $index => $match) {
            $score = $index === 2 ? [2, 2] : ($index % 2 === 0 ? [3, 1] : [1, 2]);
            $this->results->record($match, [
                'games' => [[
                    'participant_a_score' => $score[0],
                    'participant_b_score' => $score[1],
                ]],
                'duration_minutes' => 12 + $index,
                'observations' => 'Partido de fase de grupos demo.',
            ], $referee);
        }

        $tournament->matches()->where('status', MatchStatus::Completed)->update(['scheduled_at' => now()->subDay()]);
        $tournament->matches()->where('status', MatchStatus::Pending)->get()->each(
            fn (GameMatch $match, int $index) => $match->update(['scheduled_at' => now()->addHours($index + 2)]),
        );
    }

    /** @param array<int, Player> $players */
    private function createRegistrationTournament(array $players, User $organizer): void
    {
        $tournament = $this->createTournament([
            'name' => 'Clasificatorio FC Costa Rica',
            'slug' => self::REGISTRATION_SLUG,
            'description' => 'Torneo demo con inscripciones abiertas para probar altas, búsqueda e importación.',
            'max_participants' => 16,
            'format' => TournamentFormat::SingleElimination,
            'status' => TournamentStatus::Registration,
            'registration_starts_at' => now()->subDay(),
            'registration_ends_at' => now()->addWeek(),
            'starts_at' => now()->addWeeks(2),
            'ends_at' => now()->addWeeks(2)->addDay(),
            'quick_registration_enabled' => true,
            'quick_registration_levels' => ['7', '8', '9', '10', '11', '12'],
            'quick_registration_notice' => 'Debes llevar tu propio control PS4 o PS5, cargado y en buen estado.',
        ], $organizer);
        $this->registerPlayers($tournament, $players, $organizer);
    }

    private function createTournament(array $attributes, User $organizer): Tournament
    {
        return Tournament::query()->create([
            'created_by' => $organizer->id,
            'game' => GameType::EaSportsFc,
            'custom_game' => null,
            'participant_type' => ParticipantType::Individual,
            'best_of' => BestOf::One,
            ...$attributes,
        ]);
    }

    /** @param array<int, Player> $players */
    private function registerPlayers(Tournament $tournament, array $players, User $organizer): void
    {
        $tournament->players()->attach(collect($players)->mapWithKeys(
            fn (Player $player, int $index): array => [
                $player->id => [
                    'registered_by' => $organizer->id,
                    'source' => RegistrationSource::Manual->value,
                    'seed' => $index + 1,
                    'registered_at' => now(),
                ],
            ],
        )->all());
    }

    private function createDemoGameTeams(): Collection
    {
        $definitions = [
            ['Costa Rica', 'CR'], ['Argentina', 'AR'], ['Brazil', 'BR'], ['France', 'FR'],
            ['Spain', 'ES'], ['Germany', 'DE'], ['Japan', 'JP'], ['Morocco', 'MA'],
        ];

        return collect($definitions)->map(function (array $definition): GameClub {
            [$name, $countryCode] = $definition;

            $club = GameClub::query()->updateOrCreate(
                ['team_type' => GameClubType::NationalTeam->value, 'name' => $name],
                ['country_code' => $countryCode, 'is_active' => true],
            );
            $club->availabilities()->firstOrCreate(['game' => GameType::EaSportsFc->value]);

            return $club;
        });
    }

    private function assignGameClubs(Collection $clubs): void
    {
        Tournament::query()->whereIn('slug', [self::COMPLETED_SLUG, self::GROUPS_SLUG, self::REGISTRATION_SLUG])->get()
            ->each(function (Tournament $tournament) use ($clubs): void {
                $tournament->playerRegistrations()->orderBy('id')->get()->each(
                    fn ($registration, int $index) => $registration->update(['game_club_id' => $clubs[$index % $clubs->count()]->id]),
                );
            });
    }
}
