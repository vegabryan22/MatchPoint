<?php

namespace App\Services;

use App\Enums\BracketType;
use App\Enums\MatchStatus;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Models\User;
use App\Repositories\Contracts\GroupStageRepositoryInterface;
use App\Repositories\Contracts\TournamentRegistrationRepositoryInterface;
use App\Services\Groups\RoundRobinScheduleService;
use App\Services\Groups\StandingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GroupStageService
{
    public function __construct(
        private readonly GroupStageRepositoryInterface $groups,
        private readonly TournamentRegistrationRepositoryInterface $registrations,
        private readonly RoundRobinScheduleService $scheduler,
        private readonly StandingsService $standings,
        private readonly BracketGenerationService $brackets,
        private readonly AuditService $audit,
    ) {}

    public function generate(Tournament $tournament, array $data, User $actor): void
    {
        DB::transaction(function () use ($tournament, $data, $actor): void {
            $locked = Tournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();
            $this->ensureConfigurable($locked);
            $participants = $this->registrations->all($locked);
            if ($participants->count() < 3) {
                throw ValidationException::withMessages(['groups' => 'Se necesitan al menos tres participantes.']);
            }

            $groupCount = in_array($locked->format, [TournamentFormat::RoundRobin, TournamentFormat::League], true)
                ? 1 : (int) $data['group_count'];
            $qualifiers = $locked->format === TournamentFormat::GroupsKnockout ? (int) $data['qualifiers_per_group'] : 0;
            $this->validateConfiguration($locked, $participants->count(), $groupCount, $qualifiers);
            $this->groups->clear($locked);

            $createdGroups = [];
            for ($index = 0; $index < $groupCount; $index++) {
                $createdGroups[] = $this->groups->createGroup([
                    'tournament_id' => $locked->id,
                    'name' => $groupCount === 1 ? ($locked->format === TournamentFormat::League ? 'Liga' : 'Tabla general') : 'Grupo '.chr(65 + $index),
                    'position' => $index + 1,
                    'qualifiers_count' => $qualifiers,
                ]);
            }

            $ordered = $participants->sortBy(fn ($participant) => $participant->pivot->seed ?? PHP_INT_MAX)->values();
            foreach ($ordered as $index => $participant) {
                $cycle = intdiv($index, $groupCount);
                $offset = $index % $groupCount;
                $groupIndex = $cycle % 2 === 0 ? $offset : ($groupCount - 1 - $offset);
                $this->groups->addParticipant($createdGroups[$groupIndex], [
                    'participant_type' => $locked->participant_type,
                    'participant_id' => $participant->id,
                    'seed' => $index + 1,
                ]);
            }

            $schedules = collect($createdGroups)->mapWithKeys(fn ($group): array => [
                $group->id => $this->scheduler->generate($group->participants()->pluck('participant_id')->all()),
            ]);
            $matchdayCount = $schedules->max(fn (array $schedule): int => count($schedule));
            for ($matchday = 1; $matchday <= $matchdayCount; $matchday++) {
                $round = $this->groups->createRound([
                    'tournament_id' => $locked->id,
                    'name' => 'Jornada '.$matchday,
                    'number' => $matchday,
                    'bracket' => BracketType::Group,
                    'starts_at' => null,
                ]);
                $sequence = 1;
                foreach ($createdGroups as $group) {
                    foreach ($schedules[$group->id][$matchday] ?? [] as [$participantA, $participantB]) {
                        $this->groups->createMatch([
                            'tournament_id' => $locked->id,
                            'round_id' => $round->id,
                            'group_id' => $group->id,
                            'sequence' => $sequence++,
                            'participant_type' => $locked->participant_type,
                            'participant_a_id' => $participantA,
                            'participant_b_id' => $participantB,
                            'status' => MatchStatus::Pending,
                            'best_of' => $locked->best_of,
                        ]);
                    }
                }
            }

            $this->audit->record('groups.generated', $locked, [], [
                'group_count' => $groupCount,
                'qualifiers_per_group' => $qualifiers,
                'participants' => $participants->count(),
            ], $actor->id);
        });
    }

    public function details(Tournament $tournament): array
    {
        $participants = $this->registrations->all($tournament)->keyBy('id');
        $groups = $this->groups->groups($tournament);
        $standings = $groups->mapWithKeys(fn ($group): array => [$group->id => $this->standings->calculate($group, $participants)]);
        $knockoutRounds = $tournament->rounds()->where('bracket', BracketType::Main)->with('matches')->get();

        return compact('tournament', 'participants', 'groups', 'standings', 'knockoutRounds');
    }

    public function qualify(Tournament $tournament, User $actor): void
    {
        DB::transaction(function () use ($tournament, $actor): void {
            $locked = Tournament::query()->whereKey($tournament->id)->lockForUpdate()->firstOrFail();
            if ($locked->format !== TournamentFormat::GroupsKnockout || $locked->status !== TournamentStatus::InProgress) {
                throw ValidationException::withMessages(['groups' => 'La clasificación requiere un torneo de grupos en curso.']);
            }
            if ($this->groups->hasKnockoutRounds($locked)) {
                throw ValidationException::withMessages(['groups' => 'La fase eliminatoria ya fue generada.']);
            }
            if ($locked->matches()->whereNotNull('group_id')->where('status', '!=', MatchStatus::Completed)->exists()) {
                throw ValidationException::withMessages(['groups' => 'Todas las jornadas deben estar finalizadas.']);
            }

            $participants = $this->registrations->all($locked)->keyBy('id');
            $qualified = collect();
            foreach ($this->groups->groups($locked) as $group) {
                $table = $this->standings->calculate($group, $participants);
                foreach ($table->take($group->qualifiers_count) as $row) {
                    $qualified->push(['participant_id' => $row['participant_id'], 'group_id' => $group->id, 'position' => $row['position']]);
                }
            }
            if (! $this->isPowerOfTwo($qualified->count())) {
                throw ValidationException::withMessages(['groups' => 'La cantidad total de clasificados debe ser potencia de dos.']);
            }

            $pairs = $this->pairQualified($qualified->sortBy(fn (array $row): string => sprintf('%03d-%03d', $row['position'], $row['group_id']))->values()->all());
            $this->brackets->generate($locked, [
                'bracket_size' => $qualified->count(),
                'pairs' => collect($pairs)->map(fn (array $pair): array => [
                    'participant_a_id' => $pair[0]['participant_id'],
                    'participant_b_id' => $pair[1]['participant_id'],
                ])->all(),
            ]);
            $this->audit->record('groups.qualified', $locked, [], ['qualified' => $qualified->pluck('participant_id')->all()], $actor->id);
        });
    }

    private function ensureConfigurable(Tournament $tournament): void
    {
        if ($tournament->status !== TournamentStatus::Registration) {
            throw ValidationException::withMessages(['groups' => 'El calendario sólo se genera durante inscripciones.']);
        }
        if (! in_array($tournament->format, [TournamentFormat::RoundRobin, TournamentFormat::League, TournamentFormat::GroupsKnockout], true)) {
            throw ValidationException::withMessages(['groups' => 'El formato no utiliza fase de grupos o liga.']);
        }
        if ($this->groups->hasCompletedMatches($tournament) || $this->groups->hasKnockoutRounds($tournament)) {
            throw ValidationException::withMessages(['groups' => 'No se puede regenerar una competición con resultados o eliminatorias.']);
        }
    }

    private function validateConfiguration(Tournament $tournament, int $participants, int $groups, int $qualifiers): void
    {
        if ($groups < 1 || $groups > 16 || intdiv($participants, $groups) < 2) {
            throw ValidationException::withMessages(['group_count' => 'Cada grupo debe contener al menos dos participantes.']);
        }
        if ($tournament->format === TournamentFormat::GroupsKnockout) {
            $smallestGroup = intdiv($participants, $groups);
            if ($qualifiers < 1 || $qualifiers >= $smallestGroup || ! $this->isPowerOfTwo($groups * $qualifiers)) {
                throw ValidationException::withMessages(['qualifiers_per_group' => 'Los clasificados deben ser menores al tamaño del grupo y producir una potencia de dos.']);
            }
        }
    }

    private function pairQualified(array $qualified): array
    {
        $pairs = [];
        while ($qualified !== []) {
            $first = array_shift($qualified);
            $opponentIndex = null;
            for ($index = count($qualified) - 1; $index >= 0; $index--) {
                if ($qualified[$index]['group_id'] !== $first['group_id']) {
                    $opponentIndex = $index;
                    break;
                }
            }
            if ($opponentIndex === null) {
                throw ValidationException::withMessages(['groups' => 'No fue posible evitar un cruce del mismo grupo.']);
            }
            $second = $qualified[$opponentIndex];
            array_splice($qualified, $opponentIndex, 1);
            $pairs[] = [$first, $second];
        }

        return $pairs;
    }

    private function isPowerOfTwo(int $number): bool
    {
        return $number > 1 && ($number & ($number - 1)) === 0;
    }
}
