@extends('layouts.app')

@section('title', 'Torneos')

@section('content')
    <x-page-header title="Torneos" subtitle="Configuración y ciclo de vida competitivo">
        @can('create', App\Models\Tournament::class)<a class="btn btn-primary" href="{{ route('tournaments.create') }}">+ Nuevo torneo</a>@endcan
    </x-page-header>

    <div class="mp-card p-3 p-lg-4">
        <form class="row g-2 mb-4" method="get">
            <div class="col-lg-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Buscar torneo…" aria-label="Buscar torneo"></div>
            <div class="col-sm-6 col-lg-2"><select class="form-select" name="status"><option value="">Todos los estados</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-sm-6 col-lg-2"><select class="form-select" name="game"><option value="">Todos los juegos</option>@foreach($games as $game)<option value="{{ $game->value }}" @selected(request('game') === $game->value)>{{ $game->label() }}</option>@endforeach</select></div>
            <div class="col-sm-6 col-lg-2"><select class="form-select" name="format"><option value="">Todos los formatos</option>@foreach($formats as $format)<option value="{{ $format->value }}" @selected(request('format') === $format->value)>{{ $format->label() }}</option>@endforeach</select></div>
            <div class="col-6 col-lg-1"><button class="btn btn-primary w-100">Filtrar</button></div><div class="col-6 col-lg-1"><a class="btn btn-outline-secondary w-100" href="{{ route('tournaments.index') }}">×</a></div>
        </form>

        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Torneo</th><th>Juego</th><th>Formato</th><th>Inscritos</th><th>Estado</th><th>Inicio</th><th class="text-end">Acciones</th></tr></thead><tbody>
        @forelse($tournaments as $tournament)
            @php($registeredCount = $tournament->participant_type === App\Enums\ParticipantType::Individual ? $tournament->players_count : $tournament->teams_count)
            <tr><td><div class="d-flex align-items-center gap-3"><span class="mp-tournament-mark">{{ mb_strtoupper(mb_substr($tournament->name, 0, 1)) }}</span><div><a class="fw-semibold" href="{{ route('tournaments.show', $tournament) }}">{{ $tournament->name }}</a><div class="mp-muted small">{{ $tournament->participant_type->label() }}</div></div></div></td><td>{{ $tournament->gameLabel() }}</td><td>{{ $tournament->format->label() }}</td><td><a class="fw-semibold text-decoration-none" href="{{ route('tournaments.registrations.index', $tournament) }}">{{ $registeredCount }} / {{ $tournament->max_participants }}</a></td><td><span class="badge {{ $tournament->status->badgeClass() }}">{{ $tournament->status->label() }}</span></td><td class="text-nowrap">{{ $tournament->starts_at->format('d/m/Y H:i') }}</td><td><div class="d-flex justify-content-end gap-1"><a class="btn btn-sm btn-outline-secondary" href="{{ route('tournaments.show', $tournament) }}">Ver</a>@if(in_array($tournament->status, [App\Enums\TournamentStatus::Draft, App\Enums\TournamentStatus::Registration], true)) @can('update', $tournament)<a class="btn btn-sm btn-outline-primary" href="{{ route('tournaments.edit', $tournament) }}">Editar</a>@endcan @endif</div></td></tr>
        @empty<tr><td colspan="7" class="mp-empty mp-muted">No encontramos torneos con esos criterios.</td></tr>@endforelse
        </tbody></table></div>{{ $tournaments->links() }}
    </div>
@endsection
