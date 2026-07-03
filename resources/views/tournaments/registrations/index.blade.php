@extends('layouts.app')

@section('title', 'Inscripciones · '.$tournament->name)

@section('content')
    <x-page-header
        :title="'Inscripciones · '.$tournament->name"
        :subtitle="$tournament->participant_type->label().' · Capacidad '.$tournament->max_participants"
    >
        <a class="btn btn-outline-secondary" href="{{ route('tournaments.show', $tournament) }}">Volver al torneo</a>
    </x-page-header>

    <x-field-error name="registration" />
    <x-field-error name="participant_id" />
    <x-field-error name="file" />

    @can('manageRegistrations', $tournament)
        <div class="mp-card p-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><strong>Inscripciones extraordinarias</strong><div class="mp-muted small">Permite altas y retiros manuales aunque el torneo esté En curso o tenga llaves.</div></div>
            <form method="post" action="{{ route('tournaments.registrations.extraordinary', $tournament) }}">@csrf @method('PATCH')<input type="hidden" name="enabled" value="{{ $tournament->extraordinary_registration_enabled ? 0 : 1 }}"><button class="btn {{ $tournament->extraordinary_registration_enabled ? 'btn-outline-danger' : 'btn-warning' }}">{{ $tournament->extraordinary_registration_enabled ? 'Cerrar extraordinarias' : 'Habilitar extraordinarias' }}</button></form>
        </div>
    @endcan

    @if ($tournament->quick_registration_enabled)
        <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><strong>Inscripción pública activa.</strong><div class="small">{{ route('quick-registrations.create', $tournament) }}</div></div>
            <a class="btn btn-success" href="{{ route('quick-registrations.create', $tournament) }}" target="_blank">Abrir formulario</a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="mp-card p-4 h-100">
                <div class="mp-muted small">Inscritos</div>
                <div class="mp-stat-value">{{ $registeredCount }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mp-card p-4 h-100">
                <div class="mp-muted small">Cupos disponibles</div>
                <div class="mp-stat-value">{{ $remainingSlots }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mp-card p-4 h-100">
                <div class="mp-muted small mb-2">Ocupación</div>
                <div class="progress" role="progressbar" aria-label="Ocupación del torneo">
                    <div class="progress-bar" style="width: {{ min(100, ($registeredCount / $tournament->max_participants) * 100) }}%">
                        {{ round(($registeredCount / $tournament->max_participants) * 100) }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($tournament->extraordinary_registration_enabled)
        <div class="alert alert-warning"><strong>Periodo extraordinario activo.</strong> Las nuevas inscripciones podrán incluirse en una tanda nueva sin modificar las llaves existentes.</div>
    @elseif ($tournament->status !== App\Enums\TournamentStatus::Registration)
        <div class="alert alert-warning">Las altas y retiros están bloqueados porque el torneo no está en estado Inscripciones.</div>
    @elseif ($tournament->registration_starts_at?->isFuture())
        <div class="alert alert-info">El periodo abre el {{ $tournament->registration_starts_at->format('d/m/Y H:i') }}.</div>
    @elseif ($tournament->registration_ends_at?->isPast())
        <div class="alert alert-warning">El periodo cerró el {{ $tournament->registration_ends_at->format('d/m/Y H:i') }}.</div>
    @endif

    @can('manageRegistrations', $tournament)
        <div class="row g-4 mb-4">
            <div class="col-xl-7">
                <div class="mp-card p-4 h-100">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">Agregar participante</h2>
                            <p class="mp-muted mb-0">Sólo aparecen {{ $tournament->participant_type === App\Enums\ParticipantType::Individual ? 'jugadores' : 'equipos' }} activos no inscritos.</p>
                        </div>
                        <form method="get">
                            <input class="form-control" name="candidate_search" value="{{ request('candidate_search') }}" placeholder="Buscar candidato…" aria-label="Buscar candidato">
                        </form>
                    </div>

                    <form method="post" action="{{ route('tournaments.registrations.store', $tournament) }}">
                        @csrf
                        <div class="input-group">
                            <select class="form-select" name="participant_id" required @disabled($remainingSlots === 0 || ! $registrationOpen)>
                                <option value="">Seleccionar participante…</option>
                                @foreach ($candidates as $candidate)
                                    <option value="{{ $candidate->id }}">
                                        {{ $tournament->participant_type === App\Enums\ParticipantType::Individual ? $candidate->nickname.' · '.$candidate->name : $candidate->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn btn-primary" @disabled($remainingSlots === 0 || ! $registrationOpen)>Inscribir</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="mp-card p-4 h-100">
                    <h2 class="h5 fw-bold">Importar CSV</h2>
                    <p class="mp-muted small">
                        @if ($tournament->participant_type === App\Enums\ParticipantType::Individual)
                            Encabezados: <code>nickname,email</code>
                        @else
                            Encabezado: <code>name</code>
                        @endif
                    </p>
                    <form method="post" action="{{ route('tournaments.registrations.import', $tournament) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="input-group"><input class="form-control" name="file" type="file" accept=".csv,text/csv" required @disabled(! $registrationOpen)><button class="btn btn-outline-primary" @disabled(! $registrationOpen)>Importar</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @if (session('import_result'))
        @php($importResult = session('import_result'))
        <div class="alert {{ $importResult['failed'] ? 'alert-warning' : 'alert-success' }}">
            Procesadas: {{ $importResult['total'] }} · Agregadas: {{ $importResult['imported'] }} · Fallidas: {{ $importResult['failed'] }}
        </div>
        @if ($importResult['errors'])
            <div class="mp-card p-4 mb-4">
                <h2 class="h5 fw-bold">Errores de importación</h2>
                <div class="table-responsive"><table class="table"><thead><tr><th>Fila</th><th>Error</th></tr></thead><tbody>@foreach($importResult['errors'] as $error)<tr><td>{{ $error['row'] }}</td><td>{{ $error['message'] }}</td></tr>@endforeach</tbody></table></div>
            </div>
        @endif
    @endif

    <div class="mp-card p-3 p-lg-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <form class="d-flex gap-2" method="get">
                <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Buscar inscrito…" aria-label="Buscar inscrito">
                <button class="btn btn-outline-primary">Buscar</button>
            </form>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('tournaments.registrations.export.csv', $tournament) }}">CSV</a>
                <a class="btn btn-outline-success" href="{{ route('tournaments.registrations.export.xlsx', $tournament) }}">Excel</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    @if ($tournament->participant_type === App\Enums\ParticipantType::Individual)
                        <tr><th>Jugador</th><th>Nivel académico / Control</th><th>Nivel de juego</th><th>Equipo del juego</th><th>Origen</th><th>Fecha</th><th class="text-end">Acciones</th></tr>
                    @else
                        <tr><th>Equipo</th><th>Integrantes</th><th>Equipo del juego</th><th>Origen</th><th>Fecha</th><th class="text-end">Acciones</th></tr>
                    @endif
                </thead>
                <tbody>
                @forelse ($participants as $participant)
                    <tr>
                        <td>
                            <a class="fw-semibold" href="{{ $tournament->participant_type === App\Enums\ParticipantType::Individual ? route('players.show', $participant) : route('teams.show', $participant) }}">
                                {{ $tournament->participant_type === App\Enums\ParticipantType::Individual ? $participant->nickname : $participant->name }}
                            </a>
                            @if ($tournament->participant_type === App\Enums\ParticipantType::Individual)<div class="mp-muted small">{{ $participant->name }}</div>@endif
                        </td>
                        @if ($tournament->participant_type === App\Enums\ParticipantType::Individual)
                            <td>
                                @if($participant->pivot->academic_level)
                                    {{ App\Enums\AcademicLevel::from($participant->pivot->academic_level)->label() }}<div class="mp-muted small">{{ App\Enums\PlayStationController::from($participant->pivot->controller_platform)->label() }}</div>
                                @else
                                    {{ $participant->country ?? '—' }}
                                @endif
                            </td><td>{{ $participant->level->label() }}</td>
                        @else
                            <td>{{ $participant->players()->count() }}</td>
                        @endif
                        <td style="min-width: 220px">
                            @can('manageRegistrations', $tournament)
                                <form class="d-flex gap-1" method="post" action="{{ route('tournaments.registrations.game-club', [$tournament, $participant->id]) }}">@csrf @method('PATCH')
                                    <select class="form-select form-select-sm" name="game_club_id"><option value="">Sin asignar</option>@foreach($gameClubs as $club)<option value="{{ $club->id }}" @selected((int)$participant->pivot->game_club_id === $club->id)>{{ $club->countryFlag() }} {{ $club->name }}</option>@endforeach</select>
                                    <button class="btn btn-sm btn-outline-primary">Guardar</button>
                                </form>
                            @else
                                {{ $gameClubs->firstWhere('id', $participant->pivot->game_club_id)?->name ?? 'Sin asignar' }}
                            @endcan
                        </td>
                        <td><span class="badge text-bg-secondary">{{ App\Enums\RegistrationSource::from($participant->pivot->source)->label() }}</span></td>
                        <td>{{ \Illuminate\Support\Carbon::parse($participant->pivot->registered_at)->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            @can('manageRegistrations', $tournament)
                                <form method="post" action="{{ route('tournaments.registrations.destroy', [$tournament, $participant->id]) }}" data-confirm="¿Retirar esta inscripción?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Retirar</button></form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="mp-empty mp-muted">Todavía no hay participantes inscritos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $participants->links() }}
    </div>
@endsection
