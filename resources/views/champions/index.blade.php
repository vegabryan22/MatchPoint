@extends('layouts.app')

@section('title', 'Campeones históricos')

@section('content')
    <x-page-header title="Campeones históricos" subtitle="El salón de la fama de MatchPoint">
        <a class="btn btn-outline-primary" href="{{ route('statistics.index') }}">Ver estadísticas</a>
    </x-page-header>

    <form class="mp-card p-3 mb-4" method="get" action="{{ route('champions.index') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="participant_type">Modalidad</label>
                <select class="form-select" id="participant_type" name="participant_type">
                    <option value="">Todas</option>
                    @foreach (App\Enums\ParticipantType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(($filters['participant_type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="game">Juego</label>
                <select class="form-select" id="game" name="game">
                    <option value="">Todos</option>
                    @foreach (App\Enums\GameType::cases() as $game)
                        <option value="{{ $game->value }}" @selected(($filters['game'] ?? null) === $game->value)>{{ $game->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="year">Año</label>
                <select class="form-select" id="year" name="year">
                    <option value="">Todos</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected((string) ($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary">Filtrar</button></div>
        </div>
    </form>

    <div class="row g-4">
        @forelse ($champions as $champion)
            @php
                $participant = $champion->resolvedParticipant;
                $name = $participant?->nickname ?? $participant?->name ?? 'Participante eliminado';
            @endphp
            <div class="col-md-6 col-xl-4">
                <article class="mp-champion-card h-100">
                    <div class="mp-champion-glow"></div>
                    <div class="mp-trophy" aria-hidden="true">♛</div>
                    <div class="mp-eyebrow">Campeón</div>
                    <h2 class="h4 fw-bold mt-1">{{ $name }}</h2>
                    <p class="mp-muted mb-3">{{ $champion->tournament->name }}</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge text-bg-primary">{{ $champion->tournament->gameLabel() }}</span>
                        <span class="badge text-bg-secondary">{{ $champion->participant_type->label() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <div class="mp-muted small">Coronado</div>
                            <strong>{{ $champion->crowned_at->format('d/m/Y') }}</strong>
                        </div>
                        <a class="btn btn-sm btn-outline-light" href="{{ route('tournaments.show', $champion->tournament) }}">Ver torneo</a>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12"><div class="mp-card mp-empty mp-muted">Todavía no hay campeones para estos filtros.</div></div>
        @endforelse
    </div>

    <div class="mt-4">{{ $champions->links() }}</div>
@endsection
