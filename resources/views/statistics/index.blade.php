@extends('layouts.app')

@section('title', 'Estadísticas')

@section('content')
    <x-page-header title="Estadísticas" subtitle="Rendimiento calculado desde resultados oficiales">
        <a class="btn btn-outline-primary" href="{{ route('champions.index') }}">Ver campeones</a>
    </x-page-header>

    <form class="mp-card p-3 mb-4" method="get" action="{{ route('statistics.index') }}">
        <div class="row g-3 align-items-end">
            <div class="col-sm-6 col-xl-2">
                <label class="form-label" for="participant_type">Modalidad</label>
                <select class="form-select" id="participant_type" name="participant_type">
                    @foreach (App\Enums\ParticipantType::cases() as $type)
                        <option value="{{ $type->value }}" @selected($participantType === $type)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-xl-3">
                <label class="form-label" for="tournament_id">Torneo</label>
                <select class="form-select" id="tournament_id" name="tournament_id">
                    <option value="">Todos</option>
                    @foreach ($tournaments as $tournament)
                        <option value="{{ $tournament->id }}" @selected((string) ($filters['tournament_id'] ?? '') === (string) $tournament->id)>
                            {{ $tournament->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-xl-2">
                <label class="form-label" for="game">Juego</label>
                <select class="form-select" id="game" name="game">
                    <option value="">Todos</option>
                    @foreach (App\Enums\GameType::cases() as $game)
                        <option value="{{ $game->value }}" @selected(($filters['game'] ?? null) === $game->value)>{{ $game->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-xl-2">
                <label class="form-label" for="date_from">Desde</label>
                <input class="form-control" id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-sm-6 col-xl-2">
                <label class="form-label" for="date_to">Hasta</label>
                <input class="form-control" id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-sm-6 col-xl-1 d-grid">
                <button class="btn btn-primary">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="mp-card mp-stat-card p-4 h-100">
                <div class="mp-muted small">Participantes clasificados</div>
                <div class="mp-stat-value">{{ $ranking->count() }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mp-card mp-stat-card p-4 h-100">
                <div class="mp-muted small">Partidos contabilizados</div>
                <div class="mp-stat-value">{{ (int) ($ranking->sum('played') / 2) }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mp-card mp-stat-card p-4 h-100">
                <div class="mp-muted small">Líder actual</div>
                <div class="h4 fw-bold mt-2 mb-0">{{ $ranking->first()['name'] ?? 'Sin resultados' }}</div>
            </div>
        </div>
    </div>

    <div class="mp-card overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Pos.</th>
                        <th>Participante</th>
                        <th class="text-center">PJ</th>
                        <th class="text-center">V</th>
                        <th class="text-center">E</th>
                        <th class="text-center">D</th>
                        <th class="text-center">GF</th>
                        <th class="text-center">GC</th>
                        <th class="text-center">DG</th>
                        <th class="text-center">Prom.</th>
                        <th class="text-center">Racha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ranking as $row)
                        <tr>
                            <td><span class="mp-rank {{ $row['rank'] <= 3 ? 'is-podium' : '' }}">#{{ $row['rank'] }}</span></td>
                            <td>
                                <a class="fw-bold" href="{{ route('statistics.show', ['type' => $participantType->value, 'participant' => $row['participant_id'], ...request()->query()]) }}">
                                    {{ $row['name'] }}
                                </a>
                                <div class="mp-muted small">{{ $row['win_rate'] }}% victorias</div>
                            </td>
                            <td class="text-center">{{ $row['played'] }}</td>
                            <td class="text-center text-success fw-bold">{{ $row['wins'] }}</td>
                            <td class="text-center">{{ $row['draws'] }}</td>
                            <td class="text-center text-danger">{{ $row['losses'] }}</td>
                            <td class="text-center">{{ $row['goals_for'] }}</td>
                            <td class="text-center">{{ $row['goals_against'] }}</td>
                            <td class="text-center fw-bold {{ $row['goal_difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $row['goal_difference'] > 0 ? '+' : '' }}{{ $row['goal_difference'] }}
                            </td>
                            <td class="text-center">{{ number_format($row['average'], 2) }}</td>
                            <td class="text-center"><span class="badge text-bg-secondary">{{ $row['streak'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="mp-empty mp-muted" colspan="11">No existen resultados para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
