@extends('layouts.app')

@php
    $isCorrection = $match->status === App\Enums\MatchStatus::Completed;
    $participantAName = $participantA
        ? ($match->tournament->participant_type === App\Enums\ParticipantType::Individual ? $participantA->nickname : $participantA->name)
        : 'Participante A';
    $participantBName = $participantB
        ? ($match->tournament->participant_type === App\Enums\ParticipantType::Individual ? $participantB->nickname : $participantB->name)
        : 'Participante B';
    $existingScores = $match->scores->keyBy('game_number');
@endphp

@section('title', ($isCorrection ? 'Corregir' : 'Registrar').' resultado')

@section('content')
    <x-page-header
        :title="($isCorrection ? 'Corregir' : 'Registrar').' resultado'"
        :subtitle="$match->tournament->name.' · '.$match->round->name.' · Partido '.$match->sequence"
    >
        <a class="btn btn-outline-secondary" href="{{ route('tournaments.draws.show', $match->tournament) }}">Volver a la llave</a>
    </x-page-header>

    <x-field-error name="match" />
    <x-field-error name="games" />

    @if ($isCorrection)
        <div class="alert alert-warning">
            Corregir este resultado actualizará los participantes del siguiente partido. La operación se bloqueará si ese partido ya avanzó.
        </div>
    @endif

    <form
        class="needs-validation mp-result-form"
        method="post"
        action="{{ $isCorrection ? route('matches.results.update', $match) : route('matches.results.store', $match) }}"
        data-ajax-form
        novalidate
    >
        @csrf
        @if ($isCorrection)
            @method('PUT')
        @endif

        <div class="mp-card p-4 mb-4">
            <div class="mp-result-versus mb-4">
                <div class="mp-result-player">
                    @if($clubA?->crestUrl())<img class="mp-game-club-crest" src="{{ $clubA->crestUrl() }}" alt="Escudo de {{ $clubA->name }}" referrerpolicy="no-referrer">@else<span class="mp-result-side">{{ $clubA?->countryFlag() ?? 'A' }}</span>@endif
                    <div><strong>{{ $participantAName }}</strong>@if($clubA)<div class="mp-muted small">{{ $clubA->name }}</div>@endif</div>
                </div>
                <div class="mp-result-vs">VS</div>
                <div class="mp-result-player text-end">
                    <div><strong>{{ $participantBName }}</strong>@if($clubB)<div class="mp-muted small">{{ $clubB->name }}</div>@endif</div>
                    @if($clubB?->crestUrl())<img class="mp-game-club-crest" src="{{ $clubB->crestUrl() }}" alt="Escudo de {{ $clubB->name }}" referrerpolicy="no-referrer">@else<span class="mp-result-side">{{ $clubB?->countryFlag() ?? 'B' }}</span>@endif
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                <div>
                    <div class="mp-eyebrow">Marcador por juegos</div>
                    <h2 class="h5 fw-bold mb-0">{{ $match->best_of->label() }}</h2>
                </div>
                <span class="badge text-bg-secondary align-self-center">
                    {{ intdiv($match->best_of->value, 2) + 1 }} victorias necesarias
                </span>
            </div>

            <div class="mp-score-grid">
                @for ($gameNumber = 1; $gameNumber <= $match->best_of->value; $gameNumber++)
                    @php($score = $existingScores->get($gameNumber))
                    <div class="mp-score-row">
                        <div class="mp-score-game">Juego {{ $gameNumber }}</div>
                        <div>
                            <label class="visually-hidden" for="game-{{ $gameNumber }}-a">{{ $participantAName }}</label>
                            <input
                                class="form-control form-control-lg text-center fw-bold"
                                id="game-{{ $gameNumber }}-a"
                                name="games[{{ $gameNumber - 1 }}][participant_a_score]"
                                type="number"
                                min="0"
                                max="99"
                                value="{{ old('games.'.($gameNumber - 1).'.participant_a_score', $score?->participant_a_score) }}"
                                placeholder="0"
                                {{ $gameNumber === 1 ? 'required' : '' }}
                            >
                        </div>
                        <div class="mp-score-separator">—</div>
                        <div>
                            <label class="visually-hidden" for="game-{{ $gameNumber }}-b">{{ $participantBName }}</label>
                            <input
                                class="form-control form-control-lg text-center fw-bold"
                                id="game-{{ $gameNumber }}-b"
                                name="games[{{ $gameNumber - 1 }}][participant_b_score]"
                                type="number"
                                min="0"
                                max="99"
                                value="{{ old('games.'.($gameNumber - 1).'.participant_b_score', $score?->participant_b_score) }}"
                                placeholder="0"
                                {{ $gameNumber === 1 ? 'required' : '' }}
                            >
                        </div>
                    </div>
                @endfor
            </div>

            <div class="alert alert-danger d-none mt-3 mb-0" data-ajax-errors role="alert"></div>
        </div>

        <div class="mp-card p-4 mb-4">
            <h2 class="h5 fw-bold mb-3">Detalles del partido</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="duration_minutes">Duración en minutos</label>
                    <input
                        class="form-control"
                        id="duration_minutes"
                        name="duration_minutes"
                        type="number"
                        min="1"
                        max="600"
                        value="{{ old('duration_minutes', $match->duration_seconds ? (int) ceil($match->duration_seconds / 60) : null) }}"
                    >
                    <x-field-error name="duration_minutes" />
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="observations">Observaciones</label>
                    <textarea class="form-control" id="observations" name="observations" rows="3" maxlength="2000">{{ old('observations', $match->observations) }}</textarea>
                    <x-field-error name="observations" />
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('tournaments.draws.show', $match->tournament) }}">Cancelar</a>
            <button class="btn btn-primary px-4" type="submit" data-submit-button>
                {{ $isCorrection ? 'Guardar corrección' : 'Confirmar resultado' }}
            </button>
        </div>
    </form>
@endsection
