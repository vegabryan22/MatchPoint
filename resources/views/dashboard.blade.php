@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page-header title="Centro de competición" subtitle="Panorama operativo en tiempo real">
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('statistics.index') }}">Estadísticas</a>
            <a class="btn btn-primary" href="{{ route('tournaments.index') }}">Administrar torneos</a>
        </div>
    </x-page-header>

    <form class="mp-card p-3 mb-4" method="get" action="{{ route('dashboard') }}">
        <div class="row g-3 align-items-end">
            <div class="col-sm-6 col-xl-3">
                <label class="form-label" for="participant_type">Modalidad</label>
                <select class="form-select" id="participant_type" name="participant_type">
                    <option value="">Todas</option>
                    @foreach (App\Enums\ParticipantType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(($filters['participant_type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-xl-3">
                <label class="form-label" for="tournament_id">Torneo</label>
                <select class="form-select" id="tournament_id" name="tournament_id">
                    <option value="">Todos</option>
                    @foreach ($tournaments as $tournament)
                        <option value="{{ $tournament->id }}" @selected((string) ($filters['tournament_id'] ?? '') === (string) $tournament->id)>{{ $tournament->name }}</option>
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
            <div class="col-sm-6 col-xl-2 d-grid">
                <button class="btn btn-primary">Aplicar filtros</button>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        @foreach ([
            ['players', 'Total jugadores', '♙'],
            ['tournaments', 'Torneos', '◇'],
            ['pending_matches', 'Partidos pendientes', '◷'],
            ['completed_matches', 'Partidos finalizados', '✓'],
            ['total_goals', 'Goles registrados', '⚽'],
            ['teams', 'Total equipos', '⬡'],
        ] as [$key, $label, $icon])
            <div class="col-6 col-xl-2">
                <div class="mp-card mp-stat-card p-3 p-lg-4 h-100">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <div class="mp-muted small mb-2">{{ $label }}</div>
                            <div class="mp-stat-value" data-dashboard-metric="{{ $key }}">{{ $metrics[$key] }}</div>
                        </div>
                        <span class="mp-icon d-none d-md-inline-grid">{{ $icon }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div
        data-dashboard-live
        data-dashboard-url="{{ route('dashboard.data', request()->query()) }}"
        aria-live="polite"
    >
        @include('dashboard.live')
    </div>
@endsection
