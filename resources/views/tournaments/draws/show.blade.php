@extends('layouts.app')

@section('title', 'Llave · '.$tournament->name)

@section('content')
    <x-page-header :title="'Llave · '.$tournament->name" subtitle="Rondas, cruces y avance automático">
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('tournaments.show', $tournament) }}">Volver</a>
            @can('manageDraw', $tournament)
                <a class="btn btn-primary" href="{{ route('tournaments.draws.create', $tournament) }}">
                    {{ $tournament->draw ? 'Regenerar' : 'Generar sorteo' }}
                </a>
            @endcan
        </div>
    </x-page-header>

    <x-field-error name="draw" />

    @if (! $tournament->draw)
        <div class="mp-card mp-empty">
            <h2 class="h5 fw-bold">Todavía no hay llave</h2>
            <p class="mp-muted mb-0">Genera el sorteo cuando las inscripciones estén listas.</p>
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="mp-card p-4 h-100">
                    <div class="mp-muted small">Formato</div>
                    <div class="h5 fw-bold mb-0">{{ $tournament->format->label() }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="mp-card p-4 h-100">
                    <div class="mp-muted small">Método</div>
                    <div class="h5 fw-bold mb-0">{{ $tournament->draw->method->label() }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="mp-card p-4 h-100">
                    <div class="mp-muted small">Versión</div>
                    <div class="h5 fw-bold mb-0">#{{ $tournament->draw->version }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="mp-card p-4 h-100">
                    <div class="mp-muted small">Cruces históricos</div>
                    <div class="h5 fw-bold mb-0">{{ $tournament->draw->avoid_rematches ? 'Evitados' : 'Permitidos' }}</div>
                </div>
            </div>
        </div>

        @foreach (App\Enums\BracketType::cases() as $bracketType)
            @php
                $sectionRounds = $tournament->rounds
                    ->where('bracket', $bracketType)
                    ->sortBy('number');
            @endphp

            @if ($sectionRounds->isNotEmpty())
                <section class="mp-card p-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <div class="mp-eyebrow">Bracket</div>
                            <h2 class="h5 fw-bold mb-0">{{ $bracketType->label() }}</h2>
                        </div>
                        <span class="badge text-bg-secondary">{{ $sectionRounds->sum(fn ($round) => $round->matches->count()) }} partidos</span>
                    </div>

                    <div class="mp-bracket-scroll">
                        <div class="mp-bracket">
                            @foreach ($sectionRounds as $round)
                                <div class="mp-bracket-round">
                                    <div class="mp-bracket-round-title">
                                        <span>{{ $round->name }}</span>
                                        <small>{{ $round->matches->count() }} {{ Str::plural('partido', $round->matches->count()) }}</small>
                                    </div>

                                    <div class="mp-bracket-round-matches">
                                        @foreach ($round->matches as $match)
                                            @php
                                                $participantA = $participantsById->get($match->participant_a_id);
                                                $participantB = $participantsById->get($match->participant_b_id);
                                                $participantAName = $participantA
                                                    ? ($tournament->participant_type === App\Enums\ParticipantType::Individual ? $participantA->nickname : $participantA->name)
                                                    : 'Por definir';
                                                $participantBName = $participantB
                                                    ? ($tournament->participant_type === App\Enums\ParticipantType::Individual ? $participantB->nickname : $participantB->name)
                                                    : ($match->status === App\Enums\MatchStatus::Bye ? 'Pase automático' : 'Por definir');
                                                $statusClass = match ($match->status) {
                                                    App\Enums\MatchStatus::Completed => 'text-bg-success',
                                                    App\Enums\MatchStatus::Bye => 'text-bg-info',
                                                    App\Enums\MatchStatus::Cancelled => 'text-bg-secondary',
                                                    default => 'text-bg-warning',
                                                };
                                            @endphp

                                            <article class="mp-match-card {{ $match->is_conditional ? 'mp-match-conditional' : '' }}">
                                                <header class="mp-match-header">
                                                    <span>Partido {{ $match->sequence }}</span>
                                                    <span class="badge {{ $statusClass }}">{{ $match->status->label() }}</span>
                                                </header>
                                                <div class="mp-match-participant {{ $match->winner_id === $match->participant_a_id ? 'is-winner' : '' }}">
                                                    <span>{{ $participantAName }}</span>
                                                    @if ($match->winner_id === $match->participant_a_id)
                                                        <span aria-label="Ganador">✓</span>
                                                    @endif
                                                </div>
                                                <div class="mp-match-participant {{ $match->winner_id === $match->participant_b_id ? 'is-winner' : '' }}">
                                                    <span>{{ $participantBName }}</span>
                                                    @if ($match->winner_id === $match->participant_b_id)
                                                        <span aria-label="Ganador">✓</span>
                                                    @endif
                                                </div>
                                                @if ($match->is_conditional)
                                                    <footer class="mp-match-note">Se activa si gana la llave de perdedores.</footer>
                                                @endif
                                                @if (
                                                    $tournament->status === App\Enums\TournamentStatus::InProgress
                                                    && $match->participant_a_id !== null
                                                    && $match->participant_b_id !== null
                                                    && in_array($match->status, [App\Enums\MatchStatus::Pending, App\Enums\MatchStatus::Completed], true)
                                                )
                                                    @can('recordResult', $match)
                                                        <footer class="mp-match-action">
                                                            <a href="{{ route('matches.results.edit', $match) }}">
                                                                {{ $match->status === App\Enums\MatchStatus::Completed ? 'Corregir resultado' : 'Registrar resultado' }}
                                                                <span aria-hidden="true">→</span>
                                                            </a>
                                                        </footer>
                                                    @endcan
                                                @endif
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        @endforeach

        @can('manageDraw', $tournament)
            <form method="post" action="{{ route('tournaments.draws.destroy', $tournament) }}" data-confirm="¿Eliminar toda la llave y desbloquear inscripciones?">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger">Eliminar llave</button>
            </form>
        @endcan
    @endif
@endsection
