<?php

namespace Database\Seeders;

use App\Enums\BestOf;
use App\Enums\BracketType;
use App\Enums\ControllerType;
use App\Enums\DrawMethod;
use App\Enums\GameClubType;
use App\Enums\GameType;
use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Enums\PlayerLevel;
use App\Enums\RegistrationSource;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\GameClub;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use App\Services\GroupStageService;
use App\Services\TournamentDrawService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class LargeBracketDemoSeeder extends Seeder
{
    private const TOURNAMENTS = [
        32 => ['Mundial MatchPoint 32', 'mundial-matchpoint-32'],
        48 => ['Mundial MatchPoint 48 Eliminación', 'mundial-matchpoint-48-eliminacion'],
        64 => ['Mundial MatchPoint 64', 'mundial-matchpoint-64'],
    ];

    public function __construct(
        private readonly TournamentDrawService $draws,
        private readonly GroupStageService $groups,
    ) {}

    public function run(): void
    {
        $this->call([RoleSeeder::class, AdminUserSeeder::class]);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $players = $this->players();
        $nationalTeams = GameClub::query()
            ->where('team_type', GameClubType::NationalTeam)
            ->whereHas('availabilities', fn ($query) => $query->where('game', GameType::EaSportsFc->value))
            ->orderBy('name')
            ->get();

        foreach (self::TOURNAMENTS as $size => [$name, $slug]) {
            $tournament = $this->tournament($admin, $name, $slug, $size);

            if ($tournament->matches()->where('status', MatchStatus::Completed)->exists()) {
                $this->command?->warn("{$name} contiene resultados y no fue regenerado.");

                continue;
            }

            if ($tournament->draw()->exists()) {
                $this->draws->reset($tournament, $admin);
            }

            $selectedPlayers = $players->take($size)->values();
            $tournament->players()->sync($selectedPlayers->mapWithKeys(fn (Player $player): array => [
                $player->id => [
                    'registered_by' => $admin->id,
                    'source' => RegistrationSource::Manual->value,
                    'registered_at' => now(),
                ],
            ])->all());
            $this->assignNationalTeams($tournament, $selectedPlayers, $nationalTeams);
            $this->draws->generate($tournament->fresh(), [
                'method' => DrawMethod::Manual->value,
                'avoid_rematches' => false,
                'resolved_order' => $selectedPlayers->pluck('id')->all(),
            ], $admin);
        }

        $this->generateOfficialWorldCup($admin, $players->take(48)->values(), $nationalTeams);

        $this->command?->info('Llaves de 32, 48 y 64 participantes, más Mundial 48 oficial, generadas.');
    }

    /** @return Collection<int, Player> */
    private function players(): Collection
    {
        return collect(range(1, 64))->map(fn (int $number): Player => Player::query()->updateOrCreate(
            ['email' => sprintf('world-seed-%02d@matchpoint.test', $number)],
            [
                'name' => sprintf('Participante Mundial %02d', $number),
                'nickname' => sprintf('WorldSeed%02d', $number),
                'country' => 'Costa Rica',
                'preferred_controller' => ControllerType::PlayStation,
                'level' => $number <= 16 ? PlayerLevel::Advanced : PlayerLevel::Intermediate,
                'is_active' => true,
            ],
        ));
    }

    private function tournament(User $admin, string $name, string $slug, int $size): Tournament
    {
        return Tournament::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'created_by' => $admin->id,
                'name' => $name,
                'description' => "Demostración de llave mundialista simétrica para {$size} participantes.",
                'game' => GameType::EaSportsFc,
                'participant_type' => ParticipantType::Individual,
                'max_participants' => $size,
                'format' => TournamentFormat::SingleElimination,
                'best_of' => BestOf::One,
                'status' => TournamentStatus::Registration,
                'registration_starts_at' => now()->subDay(),
                'registration_ends_at' => now()->addWeek(),
                'starts_at' => now()->addWeeks(2),
                'ends_at' => now()->addWeeks(2)->addDay(),
                'quick_registration_enabled' => false,
            ],
        );
    }

    private function generateOfficialWorldCup(User $admin, Collection $players, Collection $nationalTeams): void
    {
        $tournament = Tournament::query()->firstOrCreate(
            ['slug' => 'mundial-matchpoint-48-oficial'],
            [
                'created_by' => $admin->id,
                'name' => 'Mundial MatchPoint 48 Oficial',
                'description' => 'Doce grupos de cuatro, 24 clasificados directos y ocho mejores terceros.',
                'game' => GameType::EaSportsFc,
                'participant_type' => ParticipantType::Individual,
                'max_participants' => 48,
                'format' => TournamentFormat::WorldCup48,
                'best_of' => BestOf::One,
                'status' => TournamentStatus::Registration,
                'registration_starts_at' => now()->subDay(),
                'registration_ends_at' => now()->addWeek(),
                'starts_at' => now()->addWeeks(2),
                'ends_at' => now()->addWeeks(3),
                'quick_registration_enabled' => false,
            ],
        );

        if ($tournament->rounds()->where('bracket', BracketType::Main)->exists()) {
            return;
        }

        if ($tournament->groups()->doesntExist()) {
            $tournament->update(['status' => TournamentStatus::Registration]);
            $tournament->players()->sync($players->mapWithKeys(fn (Player $player): array => [
                $player->id => [
                    'registered_by' => $admin->id,
                    'source' => RegistrationSource::Manual->value,
                    'registered_at' => now(),
                ],
            ])->all());
            $this->assignNationalTeams($tournament, $players, $nationalTeams);
            $this->groups->generate($tournament->fresh(), [
                'group_count' => 12,
                'qualifiers_per_group' => 2,
            ], $admin);
        }

        $tournament->update(['status' => TournamentStatus::InProgress]);
        foreach ($tournament->matches()->whereNotNull('group_id')->where('status', MatchStatus::Pending)->get() as $match) {
            $goals = ($match->participant_a_id % 4) + 1;
            $match->update([
                'winner_id' => $match->participant_a_id,
                'status' => MatchStatus::Completed,
                'completed_at' => now(),
            ]);
            $match->scores()->updateOrCreate(
                ['game_number' => 1],
                ['participant_a_score' => $goals, 'participant_b_score' => 0, 'winner_id' => $match->participant_a_id, 'created_by' => $admin->id],
            );
        }

        $this->groups->qualify($tournament->fresh(), $admin);
    }

    private function assignNationalTeams(Tournament $tournament, Collection $players, Collection $nationalTeams): void
    {
        if ($nationalTeams->isEmpty()) {
            return;
        }

        $players->each(function (Player $player, int $index) use ($tournament, $nationalTeams): void {
            $tournament->players()->updateExistingPivot($player->id, [
                'game_club_id' => $nationalTeams[$index % $nationalTeams->count()]->id,
            ]);
        });
    }
}
