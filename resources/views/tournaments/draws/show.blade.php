@extends('layouts.app')

@section('title', 'Llave · '.$tournament->name)

@section('content')
<x-page-header :title="$tournament->name" subtitle="Cuadro oficial del torneo">
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('tournaments.show', $tournament) }}">Volver</a>
        @if(in_array($tournament->format, [\App\Enums\TournamentFormat::SingleElimination, \App\Enums\TournamentFormat::DoubleElimination], true))
            @can('manageDraw', $tournament)
                @if($drawBatches->isNotEmpty())<a class="btn btn-primary" href="{{ route('tournaments.draws.create', [$tournament, 'mode' => 'append']) }}">+ Crear nueva tanda</a>@else<a class="btn btn-primary" href="{{ route('tournaments.draws.create', $tournament) }}">Armar primera llave</a>@endif
                @if($drawBatches->where('is_final_stage', false)->whereNotNull('winner_id')->count() >= 2 && ! $drawBatches->contains('is_final_stage', true))<a class="btn btn-warning" href="{{ route('tournaments.draws.create', [$tournament, 'mode' => 'final']) }}">Crear final entre ganadores</a>@endif
            @endcan
        @endif
    </div>
</x-page-header>

<x-field-error name="draw" />

@if ($bracketSections === [])
    <div class="mp-card mp-empty"><h2 class="h5 fw-bold">Todavía no hay llave</h2><p class="mp-muted mb-0">Las inscripciones ya pueden estar cerradas: genera ahora el sorteo para iniciar los partidos.</p></div>
@else
    <nav class="nav nav-pills gap-2 mb-4" aria-label="Tandas del torneo">
        @foreach($drawBatches as $batch)<a class="nav-link {{ $selectedDraw?->id === $batch->id ? 'active' : '' }}" href="{{ route('tournaments.draws.show', [$tournament, 'batch' => $batch->id]) }}">{{ $batch->name }}@if($batch->winner_id) ✓@endif</a>@endforeach
    </nav>
    <div class="mp-world-summary mb-4">
        <div><span>Formato</span><strong>{{ $tournament->format->label() }}</strong></div>
        <div><span>Método</span><strong>{{ $tournament->draw?->method->label() ?? 'Clasificación de grupos' }}</strong></div>
        <div><span>Participantes activos</span><strong>{{ $activeParticipantCount }}</strong></div>
        <div><span>Estado</span><strong>{{ $tournament->status->label() }}</strong></div>
    </div>

    <div class="mp-world-toolbar mb-3" data-bracket-toolbar>
        <div><div class="mp-eyebrow">Vista de competición</div><strong>Llave estilo Copa del Mundo</strong><small class="mp-muted d-block" data-bracket-live-status>Actualización automática activa</small></div>
        <div class="btn-group" role="group" aria-label="Controles de la llave">
            <button class="btn btn-outline-secondary" type="button" data-bracket-zoom="out" aria-label="Alejar">−</button>
            <button class="btn btn-outline-secondary" type="button" data-bracket-zoom="reset">100%</button>
            <button class="btn btn-outline-secondary" type="button" data-bracket-zoom="in" aria-label="Acercar">+</button>
            <button class="btn btn-outline-primary" type="button" data-bracket-fullscreen>Pantalla completa</button>
        </div>
    </div>

    <div class="mp-world-stage" data-bracket-stage data-bracket-live-url="{{ route('tournaments.draws.live', [$tournament, 'batch' => $selectedDraw?->id]) }}">
        @include('tournaments.draws._world-sections')
    </div>

    @if($tournament->draw)
        @can('manageDraw', $tournament)
            <form class="mt-4" method="post" action="{{ route('tournaments.draws.destroy', $tournament) }}" data-confirm="¿Eliminar esta tanda? Las demás llaves no serán modificadas.">@csrf @method('DELETE')<input type="hidden" name="batch" value="{{ $selectedDraw?->id }}"><button class="btn btn-outline-danger">Eliminar esta tanda</button></form>
        @endcan
    @endif
@endif
@endsection
