@extends('layouts.app')

@section('title', $tournament->name)

@section('content')
    <x-page-header :title="$tournament->name" :subtitle="$tournament->gameLabel()">
    <div class="d-flex flex-wrap gap-2">
            @if($tournament->format === App\Enums\TournamentFormat::SingleElimination)
                <a class="btn btn-outline-primary" href="{{ route('tournaments.rules.print', $tournament) }}" target="_blank">Reglamento imprimible</a>
            @endif
            @can('viewSchedule', $tournament)
                <a class="btn btn-outline-primary" href="{{ route('tournaments.schedule.index', $tournament) }}">Consolas y horarios</a>
            @endcan
            @if(auth()->user()->can('manageOrganizers', $tournament) || auth()->user()->can('manageOfficials', $tournament))<a class="btn btn-outline-primary" href="{{ route('tournaments.staff.index', $tournament) }}">Personal</a>@endif
            @can('viewRegistrations', $tournament)
                <a class="btn btn-outline-primary" href="{{ route('tournaments.registrations.index', $tournament) }}">
                    Inscripciones ({{ $registeredCount }}/{{ $tournament->max_participants }})
                </a>
            @endcan

            @if (in_array($tournament->format, [App\Enums\TournamentFormat::RoundRobin, App\Enums\TournamentFormat::League, App\Enums\TournamentFormat::GroupsKnockout, App\Enums\TournamentFormat::WorldCup48], true))
                @can('viewGroups', $tournament)
                    <a class="btn btn-outline-primary" href="{{ route('tournaments.groups.show', $tournament) }}">Grupos y calendario</a>
                @endcan
            @endif

            @if (in_array($tournament->format, [App\Enums\TournamentFormat::SingleElimination, App\Enums\TournamentFormat::DoubleElimination], true))
                @can('viewDraw', $tournament)
                    <a class="btn btn-outline-primary" href="{{ route('tournaments.draws.show', $tournament) }}">Sorteo y llave</a>
                @endcan
            @endif

            @can('duplicate', $tournament)
                <form method="post" action="{{ route('tournaments.duplicate', $tournament) }}">
                    @csrf
                    <button class="btn btn-outline-secondary">Duplicar</button>
                </form>
            @endcan

            @if (in_array($tournament->status, [App\Enums\TournamentStatus::Draft, App\Enums\TournamentStatus::Registration], true))
                @can('update', $tournament)
                    <a class="btn btn-primary" href="{{ route('tournaments.edit', $tournament) }}">Editar</a>
                @endcan
            @endif
        </div>
    </x-page-header>

    <x-field-error name="status" />
    <x-field-error name="tournament" />

    @if($publicForm)
        <x-public-form-share :tournament="$tournament" :public-form="$publicForm" />
    @endif

    <div class="mp-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <span class="badge {{ $tournament->status->badgeClass() }} mb-2">
                    {{ $tournament->status->label() }}
                </span>
                <p class="mp-muted mb-0">{{ $tournament->description ?: 'Sin descripción.' }}</p>
            </div>
            <div class="text-end">
                <div class="mp-muted small">Inicio</div>
                <strong>{{ $tournament->starts_at->format('d/m/Y H:i') }}</strong>
            </div>
        </div>

        <div class="mp-status-flow">
            @foreach (App\Enums\TournamentStatus::cases() as $status)
                <span class="mp-status-step {{ $tournament->status === $status ? 'active' : '' }}">
                    {{ $status->label() }}
                </span>
                @if (! $loop->last)
                    <span class="mp-muted">→</span>
                @endif
            @endforeach
        </div>
    </div>

    <div class="row g-4 mb-4">
        @foreach ([
            ['Modalidad', $tournament->participant_type->label()],
            ['Formato', $tournament->format->label()],
            ['Inscritos', $registeredCount.' / '.$tournament->max_participants],
            ['Presentes', $attendanceCounts['present']],
            ['Disponibles', $remainingSlots],
            ['Serie', $tournament->best_of->label()],
        ] as [$label, $value])
            <div class="col-sm-6 col-xl">
                <div class="mp-card p-4 h-100">
                    <div class="mp-muted small mb-2">{{ $label }}</div>
                    <div class="h5 fw-bold mb-0">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    @can('viewRegistrations', $tournament)
        <div class="mp-card p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <strong>Ocupación del torneo</strong>
                        <span>{{ $registeredCount }} de {{ $tournament->max_participants }}</span>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Ocupación del torneo" aria-valuenow="{{ $registeredCount }}" aria-valuemin="0" aria-valuemax="{{ $tournament->max_participants }}">
                        <div class="progress-bar" style="width: {{ min(100, ($registeredCount / $tournament->max_participants) * 100) }}%"></div>
                    </div>
                </div>
                <a class="btn btn-primary" href="{{ route('tournaments.registrations.index', $tournament) }}">
                    Ver inscritos
                </a>
            </div>
        </div>
    @endcan

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="mp-card p-4">
                <h2 class="h5 fw-bold mb-3">Calendario</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-6">Inicio de inscripciones</dt>
                    <dd class="col-sm-6">{{ $tournament->registration_starts_at?->format('d/m/Y H:i') ?? 'Por definir' }}</dd>
                    <dt class="col-sm-6">Fin de inscripciones</dt>
                    <dd class="col-sm-6">{{ $tournament->registration_ends_at?->format('d/m/Y H:i') ?? 'Por definir' }}</dd>
                    <dt class="col-sm-6">Inicio del torneo</dt>
                    <dd class="col-sm-6">{{ $tournament->starts_at->format('d/m/Y H:i') }}</dd>
                    <dt class="col-sm-6">Final estimada</dt>
                    <dd class="col-sm-6">{{ $tournament->ends_at?->format('d/m/Y H:i') ?? 'Por definir' }}</dd>
                    <dt class="col-sm-6">Creado por</dt>
                    <dd class="col-sm-6">{{ $tournament->creator?->name ?? 'Sistema' }}</dd>
                </dl>
            </div>
        </div>

        @can('update', $tournament)
            <div class="col-lg-5">
                <div class="mp-card p-4 mb-4">
                    <h2 class="h5 fw-bold">Cambiar estado</h2>
                    <p class="mp-muted">Sólo se muestran transiciones válidas desde el estado actual.</p>

                    @forelse ($transitions as $transition)
                        <form class="d-inline-block me-1 mb-2" method="post" action="{{ route('tournaments.status', $tournament) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $transition->value }}">
                            <button class="btn btn-outline-primary">{{ $transition->label() }}</button>
                        </form>
                    @empty
                        <div class="alert alert-secondary mb-0">Este estado no admite más transiciones.</div>
                    @endforelse
                </div>

                @can('delete', $tournament)
                    <div class="mp-card p-4 border-danger">
                        <h2 class="h5 fw-bold text-danger">Zona de riesgo</h2>
                        <p class="mp-muted">Sólo pueden eliminarse borradores y torneos cancelados.</p>
                        <form method="post" action="{{ route('tournaments.destroy', $tournament) }}" data-confirm="¿Eliminar definitivamente este torneo?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger w-100">Eliminar torneo</button>
                        </form>
                    </div>
                @endcan
            </div>
        @endcan
    </div>
@endsection
