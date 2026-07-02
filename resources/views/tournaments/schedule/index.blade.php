@extends('layouts.app')

@section('title', 'Consolas y horarios · '.$tournament->name)

@section('content')
    <x-page-header
        :title="'Consolas y horarios · '.$tournament->name"
        subtitle="Capacidad operativa y programación automática de partidos"
    >
        <a class="btn btn-outline-secondary" href="{{ route('tournaments.show', $tournament) }}">Volver al torneo</a>
    </x-page-header>

    <x-field-error name="schedule" />
    <x-field-error name="starts_at" />
    <x-field-error name="target_hours" />

    <div class="row g-3 mb-4">
        @foreach ([
            ['Consolas activas', $activeStationCount],
            ['Duración por partido', $tournament->match_duration_minutes.' min'],
            ['Preparación', $tournament->turnaround_minutes.' min'],
            ['Partidos programados', $scheduledMatchCount],
            ['Final estimada', $estimatedEndAt?->format('d/m/Y H:i') ?? 'Sin calcular'],
        ] as [$label, $value])
            <div class="col-6 col-xl">
                <div class="mp-card p-3 p-lg-4 h-100">
                    <div class="mp-muted small mb-1">{{ $label }}</div>
                    <div class="h5 fw-bold mb-0">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <section class="mp-card p-3 p-lg-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <div class="mp-eyebrow">Calculador de capacidad</div>
                <h2 class="h4 fw-bold mb-1">¿Cuánto durará el torneo?</h2>
                <p class="mp-muted mb-0">Proyección teórica por rondas; la programación final también considera ventanas de disponibilidad.</p>
            </div>
            <span class="badge {{ $capacity['uses_generated_structure'] ? 'text-bg-success' : 'text-bg-warning' }}">
                {{ $capacity['uses_generated_structure'] ? 'Estructura real' : 'Proyección por formato' }}
            </span>
        </div>

        <div class="row g-3 mb-4">
            @foreach ([
                ['Participantes inscritos', $capacity['participant_count']],
                ['Partidos estimados', $capacity['match_count']],
                ['Rondas o jornadas', $capacity['round_count']],
                ['Consolas activas', $capacity['active_stations']],
                ['Duración actual', $capacity['current_duration_label'] ?? 'Agregue consolas'],
            ] as [$label, $value])
                <div class="col-6 col-lg">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="mp-muted small">{{ $label }}</div>
                        <div class="h5 fw-bold mb-0">{{ $value }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($capacity['match_count'] > 0)
            <form class="row g-3 align-items-end mb-4" method="get" action="{{ route('tournaments.schedule.index', $tournament) }}">
                <div class="col-md-5">
                    <label class="form-label">Quiero terminar el torneo en</label>
                    <div class="input-group">
                        <input class="form-control" name="target_hours" type="number" min="0" max="168" value="{{ request('target_hours', 4) }}" aria-label="Horas objetivo">
                        <span class="input-group-text">horas</span>
                        <input class="form-control" name="target_minutes" type="number" min="0" max="59" value="{{ request('target_minutes', 0) }}" aria-label="Minutos objetivo">
                        <span class="input-group-text">min</span>
                    </div>
                </div>
                <div class="col-md-3"><button class="btn btn-primary w-100">Calcular mínimo</button></div>
                <div class="col-md-4">
                    @if ($capacity['target_minutes'] !== null)
                        @if ($capacity['minimum_stations'] !== null)
                            <div class="alert alert-success mb-0 py-2">
                                Mínimo: <strong>{{ $capacity['minimum_stations'] }} {{ $capacity['minimum_stations'] === 1 ? 'consola' : 'consolas' }}</strong> para completar en {{ $capacity['target_label'] }}.
                            </div>
                        @else
                            <div class="alert alert-danger mb-0 py-2">
                                Meta imposible. El mínimo por dependencias entre rondas es {{ $capacity['minimum_possible_label'] }}.
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info mb-0 py-2">Ingrese una meta para calcular las consolas mínimas.</div>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Consolas</th><th>Duración estimada</th><th>Comparación</th></tr></thead>
                    <tbody>
                        @foreach ($capacity['scenarios'] as $scenario)
                            <tr class="{{ $scenario['stations'] === $capacity['minimum_stations'] ? 'table-success' : '' }}">
                                <td><strong>{{ $scenario['stations'] }}</strong>@if($scenario['stations'] === $capacity['active_stations']) <span class="badge text-bg-primary">Actual</span>@endif</td>
                                <td>{{ $scenario['duration_label'] }}</td>
                                <td>
                                    @if ($capacity['target_minutes'] === null)
                                        <span class="mp-muted">Sin meta</span>
                                    @elseif ($scenario['meets_target'])
                                        <span class="badge text-bg-success">Cumple</span>
                                    @else
                                        <span class="badge text-bg-warning">No cumple</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info mb-0">Se necesitan al menos dos participantes inscritos para calcular la duración.</div>
        @endif
    </section>

    @can('manageSchedule', $tournament)
        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <section class="mp-card p-4 h-100">
                    <h2 class="h5 fw-bold">Tiempos del torneo</h2>
                    <p class="mp-muted">Cada bloque incluye juego y preparación antes del siguiente partido.</p>
                    <form class="row g-3" method="post" action="{{ route('tournaments.schedule.configure', $tournament) }}">
                        @csrf
                        @method('PUT')
                        <div class="col-sm-6">
                            <label class="form-label" for="match_duration_minutes">Partido (minutos)</label>
                            <input class="form-control" id="match_duration_minutes" name="match_duration_minutes" type="number" min="5" max="180" value="{{ old('match_duration_minutes', $tournament->match_duration_minutes) }}" required>
                            <x-field-error name="match_duration_minutes" />
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="turnaround_minutes">Preparación (minutos)</label>
                            <input class="form-control" id="turnaround_minutes" name="turnaround_minutes" type="number" min="0" max="60" value="{{ old('turnaround_minutes', $tournament->turnaround_minutes) }}" required>
                            <x-field-error name="turnaround_minutes" />
                        </div>
                        <div class="col-12"><button class="btn btn-primary w-100">Guardar tiempos</button></div>
                    </form>
                </section>
            </div>

            <div class="col-lg-7">
                <section class="mp-card p-4 h-100">
                    <h2 class="h5 fw-bold">Agregar consola o estación</h2>
                    <form class="row g-3" method="post" action="{{ route('tournaments.stations.store', $tournament) }}">
                        @csrf
                        <input name="is_active" type="hidden" value="1">
                        <div class="col-sm-6">
                            <label class="form-label" for="station_name">Nombre</label>
                            <input class="form-control" id="station_name" name="name" placeholder="Consola 1" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="station_platform">Plataforma</label>
                            <select class="form-select" id="station_platform" name="platform" required>
                                @foreach ($platforms as $platform)
                                    <option value="{{ $platform->value }}">{{ $platform->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" for="station_location">Ubicación</label>
                            <input class="form-control" id="station_location" name="location" placeholder="Aula o mesa">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" for="station_available_from">Disponible desde</label>
                            <input class="form-control js-datetime-picker" id="station_available_from" name="available_from" type="text" autocomplete="off" placeholder="Opcional">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" for="station_available_until">Disponible hasta</label>
                            <input class="form-control js-datetime-picker" id="station_available_until" name="available_until" type="text" autocomplete="off" placeholder="Opcional">
                        </div>
                        <div class="col-12"><button class="btn btn-primary w-100">Agregar consola</button></div>
                    </form>
                </section>
            </div>
        </div>
    @endcan

    <section class="mp-card p-3 p-lg-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div><h2 class="h5 fw-bold mb-1">Consolas disponibles</h2><p class="mp-muted mb-0">El horario sólo utiliza estaciones activas.</p></div>
            <span class="badge text-bg-primary">{{ $activeStationCount }} activas</span>
        </div>

        <div class="row g-3">
            @forelse ($stations as $station)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded-3 p-3 h-100">
                        @can('manageSchedule', $tournament)
                            <form class="row g-2" method="post" action="{{ route('tournaments.stations.update', [$tournament, $station]) }}">
                                @csrf
                                @method('PUT')
                                <div class="col-12 d-flex justify-content-between align-items-center gap-2">
                                    <input class="form-control fw-bold" name="name" value="{{ $station->name }}" aria-label="Nombre de consola" required>
                                    <span class="badge {{ $station->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $station->is_active ? 'Activa' : 'Inactiva' }}</span>
                                </div>
                                <div class="col-12"><select class="form-select" name="platform">@foreach($platforms as $platform)<option value="{{ $platform->value }}" @selected($station->platform === $platform)>{{ $platform->label() }}</option>@endforeach</select></div>
                                <div class="col-12"><input class="form-control" name="location" value="{{ $station->location }}" placeholder="Ubicación"></div>
                                <div class="col-6"><input class="form-control js-datetime-picker" name="available_from" value="{{ $station->available_from?->format('Y-m-d H:i') }}" placeholder="Desde" aria-label="Disponible desde"></div>
                                <div class="col-6"><input class="form-control js-datetime-picker" name="available_until" value="{{ $station->available_until?->format('Y-m-d H:i') }}" placeholder="Hasta" aria-label="Disponible hasta"></div>
                                <div class="col-12 form-check ms-1"><input name="is_active" type="hidden" value="0"><input class="form-check-input" id="station-active-{{ $station->id }}" name="is_active" type="checkbox" value="1" @checked($station->is_active)><label class="form-check-label" for="station-active-{{ $station->id }}">Disponible para programar</label></div>
                                <div class="col-12 d-flex justify-content-between align-items-center"><span class="mp-muted small">{{ $station->matches_count }} partidos asignados</span><button class="btn btn-sm btn-outline-primary">Guardar</button></div>
                            </form>
                            <form class="mt-2" method="post" action="{{ route('tournaments.stations.destroy', [$tournament, $station]) }}" data-confirm="¿Retirar esta consola del torneo?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger w-100">Retirar</button></form>
                        @else
                            <div class="d-flex justify-content-between"><strong>{{ $station->name }}</strong><span class="badge {{ $station->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $station->is_active ? 'Activa' : 'Inactiva' }}</span></div>
                            <div class="mp-muted">{{ $station->platform->label() }} · {{ $station->location ?: 'Sin ubicación' }}</div>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="mp-empty mp-muted">Todavía no hay consolas configuradas.</div></div>
            @endforelse
        </div>
    </section>

    @can('manageSchedule', $tournament)
        <section class="mp-card p-4 mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-lg-7">
                    <h2 class="h5 fw-bold">Generar programación</h2>
                    <p class="mp-muted mb-0">Distribuye cada ronda entre las consolas activas y calcula la hora final.</p>
                </div>
                <div class="col-lg-5">
                    <form class="d-flex gap-2" method="post" action="{{ route('tournaments.schedule.generate', $tournament) }}">
                        @csrf
                        <input class="form-control js-datetime-picker" name="starts_at" value="{{ $tournament->starts_at->format('Y-m-d H:i') }}" aria-label="Inicio de programación" required>
                        <button class="btn btn-primary text-nowrap">Generar horario</button>
                    </form>
                </div>
                @if ($scheduledMatchCount > 0)
                    <div class="col-12"><form method="post" action="{{ route('tournaments.schedule.clear', $tournament) }}" data-confirm="¿Eliminar la programación de partidos pendientes?">@csrf @method('DELETE')<button class="btn btn-outline-danger">Limpiar horario pendiente</button></form></div>
                @endif
            </div>
        </section>
    @endcan

    <section class="mp-card p-3 p-lg-4">
        <h2 class="h5 fw-bold mb-3">Horario de partidos</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Hora</th><th>Consola</th><th>Fase</th><th>Enfrentamiento</th><th>Estado</th></tr></thead>
                <tbody>
                    @forelse ($scheduledMatches as $match)
                        @php($participantA = $participants->get($match->participant_a_id))
                        @php($participantB = $participants->get($match->participant_b_id))
                        <tr>
                            <td class="text-nowrap"><strong>{{ $match->scheduled_at->format('d/m H:i') }}</strong><div class="mp-muted small">hasta {{ $match->scheduled_end_at?->format('H:i') }}</div></td>
                            <td><strong>{{ $match->station?->name ?? 'Sin asignar' }}</strong><div class="mp-muted small">{{ $match->station?->location }}</div></td>
                            <td>{{ $match->round?->name ?? 'Partido' }}@if($match->group)<div class="mp-muted small">{{ $match->group->name }}</div>@endif</td>
                            <td>{{ $participantA?->nickname ?? $participantA?->name ?? 'Por definir' }} <span class="mp-muted">vs</span> {{ $participantB?->nickname ?? $participantB?->name ?? 'Por definir' }}</td>
                            <td><span class="badge {{ $match->status->badgeClass() }}">{{ $match->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="mp-empty mp-muted">Aún no se ha generado el horario.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
