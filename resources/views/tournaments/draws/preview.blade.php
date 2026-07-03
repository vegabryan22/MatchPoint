@extends('layouts.app')

@section('title', 'Vista previa · '.$tournament->name)

@section('content')
    <x-page-header title="Vista previa del sorteo" :subtitle="$plan['method']->label().' · '.$tournament->name">
        <a class="btn btn-outline-secondary" href="{{ route('tournaments.draws.create', $tournament) }}">Cambiar configuración</a>
    </x-page-header>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="mp-card p-4 h-100">
                <h2 class="h5 fw-bold mb-3">Orden de semillas</h2>
                <ol class="list-group list-group-numbered">
                    @foreach ($plan['seeded_participants'] as $seeded)
                        <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center">
                            <span>{{ $tournament->participant_type === App\Enums\ParticipantType::Individual ? $seeded['participant']->nickname : $seeded['participant']->name }}</span>
                            <span class="badge text-bg-secondary">#{{ $seeded['seed'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="mp-card p-4 h-100">
                <div class="d-flex justify-content-between gap-2">
                    <h2 class="h5 fw-bold">{{ $plan['repechage'] ? 'Ronda clasificatoria · todos juegan' : 'Primera ronda' }}</h2>
                    <span class="mp-muted">
                        @if($plan['repechage'])
                            {{ $plan['preliminary_count'] }} partidos · {{ $plan['preliminary_count'] }} ganadores + {{ $plan['best_loser_count'] }} mejores perdedores a {{ $plan['bracket_size'] }}
                        @else
                            {{ $plan['bracket_size'] }} participantes
                        @endif
                    </span>
                </div>
                <div class="row g-3 mt-1">
                    @foreach ($plan['pairs'] as $index => $pair)
                        <div class="col-md-6">
                            <div class="mp-match-card w-100">
                                <div class="px-3 py-2 mp-muted small">Partido {{ $index + 1 }}</div>
                                <div class="mp-match-participant"><span>{{ $tournament->participant_type === App\Enums\ParticipantType::Individual ? $pair['participant_a']->nickname : $pair['participant_a']->name }}</span></div>
                                <div class="mp-match-participant"><span>{{ $tournament->participant_type === App\Enums\ParticipantType::Individual ? $pair['participant_b']->nickname : $pair['participant_b']->name }}</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="{{ route('tournaments.draws.store', $tournament) }}" data-confirm="¿Confirmar este sorteo? Las inscripciones quedarán bloqueadas.">
        @csrf
        <input type="hidden" name="method" value="{{ $plan['method']->value }}">
        <input type="hidden" name="avoid_rematches" value="{{ $plan['avoid_rematches'] ? 1 : 0 }}">
        @foreach ($plan['order'] as $participantId)<input type="hidden" name="resolved_order[]" value="{{ $participantId }}">@endforeach
        <button class="btn btn-primary">Confirmar y generar llave</button>
    </form>
@endsection
