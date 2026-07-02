<?php

namespace App\Services;

use App\Enums\TournamentFormat;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\TournamentStation;
use App\Repositories\Contracts\TournamentScheduleRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TournamentScheduleService
{
    public function __construct(
        private readonly TournamentScheduleRepositoryInterface $schedules,
        private readonly AuditService $audit,
    ) {}

    public function overview(Tournament $tournament): array
    {
        $stations = $this->schedules->stations($tournament);
        $matches = $this->schedules->scheduledMatches($tournament);
        $participants = $tournament->participant_type->value === 'individual'
            ? $tournament->players()->get()->keyBy('id')
            : $tournament->teams()->get()->keyBy('id');

        return [
            'stations' => $stations,
            'scheduledMatches' => $matches,
            'participants' => $participants,
            'activeStationCount' => $stations->where('is_active', true)->count(),
            'scheduledMatchCount' => $matches->count(),
            'estimatedEndAt' => $matches->max('scheduled_end_at'),
        ];
    }

    public function capacityAnalysis(Tournament $tournament, ?int $targetMinutes = null): array
    {
        $participantCount = $tournament->participant_type->value === 'individual'
            ? $tournament->players()->count()
            : $tournament->teams()->count();
        $matches = $this->schedules->playableMatches($tournament);
        $usesGeneratedStructure = $matches->isNotEmpty();
        $roundCounts = $usesGeneratedStructure
            ? $matches->groupBy(fn (GameMatch $match): string => (string) ($match->round_id ?? 0))->map->count()->values()->all()
            : $this->projectRoundCounts($tournament->format, $participantCount);
        $matchCount = array_sum($roundCounts);
        $activeStations = $this->schedules->stations($tournament)->where('is_active', true)->count();
        $duration = $tournament->match_duration_minutes;
        $turnaround = $tournament->turnaround_minutes;
        $currentDuration = $activeStations > 0
            ? $this->estimateDuration($roundCounts, $activeStations, $duration, $turnaround)
            : null;
        $minimumPossible = $matchCount > 0
            ? $this->estimateDuration($roundCounts, max($roundCounts), $duration, $turnaround)
            : 0;
        $minimumStations = null;

        if ($targetMinutes !== null && $matchCount > 0) {
            foreach (range(1, max($roundCounts)) as $stationCount) {
                if ($this->estimateDuration($roundCounts, $stationCount, $duration, $turnaround) <= $targetMinutes) {
                    $minimumStations = $stationCount;
                    break;
                }
            }
        }

        $scenarioCounts = $matchCount > 0
            ? collect(range(1, min(max($roundCounts), 12)))
                ->push($activeStations > 0 ? $activeStations : null, $minimumStations)
                ->filter()
                ->unique()
                ->sort()
                ->values()
            : collect();

        return [
            'participant_count' => $participantCount,
            'match_count' => $matchCount,
            'round_count' => count($roundCounts),
            'round_counts' => $roundCounts,
            'active_stations' => $activeStations,
            'current_duration_minutes' => $currentDuration,
            'current_duration_label' => $this->durationLabel($currentDuration),
            'target_minutes' => $targetMinutes,
            'target_label' => $this->durationLabel($targetMinutes),
            'minimum_stations' => $minimumStations,
            'minimum_possible_minutes' => $minimumPossible,
            'minimum_possible_label' => $this->durationLabel($minimumPossible),
            'is_target_possible' => $targetMinutes === null || $targetMinutes >= $minimumPossible,
            'uses_generated_structure' => $usesGeneratedStructure,
            'scenarios' => $scenarioCounts->map(function (int $stations) use ($roundCounts, $duration, $turnaround, $targetMinutes): array {
                $minutes = $this->estimateDuration($roundCounts, $stations, $duration, $turnaround);

                return [
                    'stations' => $stations,
                    'duration_minutes' => $minutes,
                    'duration_label' => $this->durationLabel($minutes),
                    'meets_target' => $targetMinutes !== null && $minutes <= $targetMinutes,
                ];
            })->all(),
        ];
    }

    public function configure(Tournament $tournament, array $attributes): Tournament
    {
        $before = $tournament->only(['match_duration_minutes', 'turnaround_minutes']);
        $updated = $this->schedules->updateSettings($tournament, $attributes);
        $this->audit->record('tournament.schedule_configured', $updated, $before, $attributes);

        return $updated;
    }

    public function createStation(Tournament $tournament, array $attributes): TournamentStation
    {
        $station = $this->schedules->createStation($tournament, $attributes);
        $this->audit->record('tournament.station_created', $station, [], $station->toArray());

        return $station;
    }

    public function updateStation(Tournament $tournament, TournamentStation $station, array $attributes): TournamentStation
    {
        $this->ensureStationBelongsToTournament($tournament, $station);
        $before = $station->toArray();
        $updated = $this->schedules->updateStation($station, $attributes);
        $this->audit->record('tournament.station_updated', $updated, $before, $updated->toArray());

        return $updated;
    }

    public function deleteStation(Tournament $tournament, TournamentStation $station): void
    {
        $this->ensureStationBelongsToTournament($tournament, $station);
        $before = $station->toArray();
        $this->schedules->deleteStation($station);
        $this->audit->record('tournament.station_deleted', $station, $before, []);
    }

    public function generate(Tournament $tournament, ?string $startsAt = null): array
    {
        $stations = $this->schedules->stations($tournament)->where('is_active', true)->values();
        $matches = $this->schedules->schedulableMatches($tournament);

        if ($stations->isEmpty()) {
            throw ValidationException::withMessages(['schedule' => 'Debe habilitar al menos una consola o estación.']);
        }

        if ($matches->isEmpty()) {
            throw ValidationException::withMessages(['schedule' => 'Primero debe generar la llave, los grupos o el calendario del torneo.']);
        }

        $start = CarbonImmutable::parse($startsAt ?: $tournament->starts_at);
        $duration = $tournament->match_duration_minutes;
        $turnaround = $tournament->turnaround_minutes;
        $stationAvailability = $stations->mapWithKeys(function (TournamentStation $station) use ($start): array {
            $availableFrom = $station->available_from === null
                ? $start
                : CarbonImmutable::instance($station->available_from)->max($start);

            return [$station->id => $availableFrom];
        });

        $roundGroups = $matches
            ->sortBy(fn (GameMatch $match): string => sprintf(
                '%05d-%s-%05d',
                $match->round?->number ?? 0,
                $match->round?->bracket->value ?? 'none',
                $match->sequence,
            ))
            ->groupBy(fn (GameMatch $match): string => ($match->round_id ?? 0).'-'.($match->round?->bracket->value ?? 'none'));

        $assignments = [];
        $roundStart = $start;

        foreach ($roundGroups as $roundMatches) {
            $roundEnds = collect();
            foreach ($roundMatches as $match) {
                [$station, $matchStart] = $this->nextStation($stations, $stationAvailability, $roundStart, $duration);
                $matchEnd = $matchStart->addMinutes($duration);
                $assignments[] = [
                    'match' => $match,
                    'station' => $station,
                    'starts_at' => $matchStart,
                    'ends_at' => $matchEnd,
                ];
                $roundEnds->push($matchEnd);
                $stationAvailability[$station->id] = $matchEnd->addMinutes($turnaround);
            }

            $roundStart = CarbonImmutable::instance($roundEnds->sortDesc()->first())->addMinutes($turnaround);
        }

        DB::transaction(function () use ($tournament, $assignments): void {
            $this->schedules->clearPendingSchedule($tournament);
            foreach ($assignments as $assignment) {
                $this->schedules->scheduleMatch($assignment['match'], [
                    'tournament_station_id' => $assignment['station']->id,
                    'scheduled_at' => $assignment['starts_at'],
                    'scheduled_end_at' => $assignment['ends_at'],
                ]);
            }
        });

        $estimatedEndAt = collect($assignments)->max('ends_at');
        $this->audit->record('tournament.schedule_generated', $tournament, [], [
            'matches' => count($assignments),
            'stations' => $stations->count(),
            'starts_at' => $start->toDateTimeString(),
            'ends_at' => $estimatedEndAt?->toDateTimeString(),
        ]);

        return ['matches' => count($assignments), 'ends_at' => $estimatedEndAt];
    }

    public function clear(Tournament $tournament): void
    {
        $this->schedules->clearPendingSchedule($tournament);
        $this->audit->record('tournament.schedule_cleared', $tournament);
    }

    private function nextStation(Collection $stations, $availability, CarbonImmutable $roundStart, int $duration): array
    {
        $candidate = $stations
            ->map(function (TournamentStation $station) use ($availability, $roundStart, $duration): ?array {
                $startsAt = CarbonImmutable::instance($availability[$station->id])->max($roundStart);
                $endsAt = $startsAt->addMinutes($duration);
                if ($station->available_until !== null && $endsAt->isAfter($station->available_until)) {
                    return null;
                }

                return ['station' => $station, 'starts_at' => $startsAt];
            })
            ->filter()
            ->sortBy(fn (array $entry) => $entry['starts_at']->timestamp)
            ->first();

        if ($candidate === null) {
            throw ValidationException::withMessages([
                'schedule' => 'La disponibilidad de las consolas no alcanza para programar todos los partidos.',
            ]);
        }

        return [$candidate['station'], $candidate['starts_at']];
    }

    /** @return list<int> */
    private function projectRoundCounts(TournamentFormat $format, int $participants): array
    {
        if ($participants < 2) {
            return [];
        }

        return match ($format) {
            TournamentFormat::SingleElimination => $this->singleEliminationRounds($participants),
            TournamentFormat::DoubleElimination => $this->doubleEliminationRounds($participants),
            TournamentFormat::RoundRobin, TournamentFormat::League => $this->roundRobinRounds($participants),
            TournamentFormat::GroupsKnockout => $this->groupsKnockoutRounds($participants),
            TournamentFormat::WorldCup48 => $participants === 48 ? [24, 24, 24, 16, 8, 4, 2, 1] : $this->groupsKnockoutRounds($participants),
        };
    }

    /** @return list<int> */
    private function singleEliminationRounds(int $participants): array
    {
        $rounds = [];
        while ($participants > 1) {
            $matches = intdiv($participants, 2);
            if ($matches > 0) {
                $rounds[] = $matches;
            }
            $participants = (int) ceil($participants / 2);
        }

        return $rounds;
    }

    /** @return list<int> */
    private function doubleEliminationRounds(int $participants): array
    {
        $winnerRounds = $this->singleEliminationRounds($participants);
        $loserMatches = max(0, $participants - 2);
        $loserRounds = [];
        $roundCapacity = max(1, intdiv($participants, 2));

        while ($loserMatches > 0) {
            $matches = min($roundCapacity, $loserMatches);
            $loserRounds[] = $matches;
            $loserMatches -= $matches;
            $roundCapacity = max(1, intdiv($roundCapacity, 2));
        }

        return [...$winnerRounds, ...$loserRounds, 1];
    }

    /** @return list<int> */
    private function roundRobinRounds(int $participants): array
    {
        $roundCount = $participants % 2 === 0 ? $participants - 1 : $participants;

        return array_fill(0, $roundCount, intdiv($participants, 2));
    }

    /** @return list<int> */
    private function groupsKnockoutRounds(int $participants): array
    {
        $groupCount = max(1, (int) ceil($participants / 4));
        $groupSizes = array_fill(0, $groupCount, intdiv($participants, $groupCount));
        $remainder = $participants % $groupCount;
        for ($index = 0; $index < $remainder; $index++) {
            $groupSizes[$index]++;
        }
        $matchdays = max(array_map(fn (int $size): int => $size % 2 === 0 ? $size - 1 : $size, $groupSizes));
        $groupRounds = [];
        for ($day = 0; $day < $matchdays; $day++) {
            $groupRounds[] = array_sum(array_map(
                fn (int $size): int => $day < ($size % 2 === 0 ? $size - 1 : $size) ? intdiv($size, 2) : 0,
                $groupSizes,
            ));
        }
        $qualified = min($participants, 2 ** (int) floor(log(max(2, $groupCount * 2), 2)));

        return [...array_filter($groupRounds), ...$this->singleEliminationRounds($qualified)];
    }

    /** @param list<int> $roundCounts */
    private function estimateDuration(array $roundCounts, int $stations, int $duration, int $turnaround): int
    {
        if ($roundCounts === []) {
            return 0;
        }

        $slotMinutes = $duration + $turnaround;
        $total = array_sum(array_map(
            fn (int $matches): int => (int) ceil($matches / max(1, $stations)) * $slotMinutes,
            $roundCounts,
        ));

        return max(0, $total - $turnaround);
    }

    private function durationLabel(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $hours > 0 ? sprintf('%d h %02d min', $hours, $remainingMinutes) : "{$remainingMinutes} min";
    }

    private function ensureStationBelongsToTournament(Tournament $tournament, TournamentStation $station): void
    {
        if ($station->tournament_id !== $tournament->id) {
            abort(404);
        }
    }
}
