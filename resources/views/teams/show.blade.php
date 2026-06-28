@extends('layouts.app')

@section('title', $team->name)

@section('content')
    <x-page-header :title="$team->name" subtitle="Perfil y plantilla competitiva">@can('update', $team)<a class="btn btn-primary" href="{{ route('teams.edit', $team) }}">Editar equipo</a>@endcan</x-page-header>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="mp-card p-4 mb-4">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-4">
                    @if($team->logoUrl())<img class="mp-team-logo mp-team-logo-lg" src="{{ $team->logoUrl() }}" alt="Logo de {{ $team->name }}">@else<span class="mp-team-logo mp-team-logo-lg">{{ mb_strtoupper(mb_substr($team->name, 0, 1)) }}</span>@endif
                    <div><span class="badge {{ $team->is_active ? 'text-bg-success' : 'text-bg-danger' }} mb-2">{{ $team->is_active ? 'Disponible' : 'Inactivo' }}</span><h2 class="h3 fw-bold">{{ $team->name }}</h2><p class="mp-muted mb-0">{{ $team->description ?: 'Sin descripción.' }}</p></div>
                </div>
            </div>
            <div class="mp-card p-4"><h2 class="h5 fw-bold mb-3">Plantilla</h2><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Jugador</th><th>País</th><th>Función</th></tr></thead><tbody>@forelse($team->players as $player)<tr><td><a class="fw-semibold" href="{{ route('players.show', $player) }}">{{ $player->nickname }}</a><div class="mp-muted small">{{ $player->name }}</div></td><td>{{ $player->country }}</td><td>@if($player->pivot->is_captain)<span class="badge text-bg-primary">Capitán</span>@else<span class="mp-muted">Integrante</span>@endif</td></tr>@empty<tr><td colspan="3" class="mp-empty mp-muted">Este equipo todavía no tiene integrantes.</td></tr>@endforelse</tbody></table></div></div>
        </div>
        @can('update', $team)
            <div class="col-lg-4"><div class="mp-card p-4 mb-4"><h2 class="h5 fw-bold">Disponibilidad</h2><p class="mp-muted">Los equipos inactivos no podrán registrarse en futuras competencias.</p><form method="post" action="{{ route('teams.status', $team) }}">@csrf @method('PATCH')<button class="btn {{ $team->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} w-100">{{ $team->is_active ? 'Desactivar' : 'Activar' }}</button></form></div>@can('delete', $team)<div class="mp-card p-4 border-danger"><h2 class="h5 fw-bold text-danger">Zona de riesgo</h2><p class="mp-muted">Eliminar el equipo libera su plantilla y borra el logo.</p><form method="post" action="{{ route('teams.destroy', $team) }}" data-confirm="¿Eliminar definitivamente {{ $team->name }}?">@csrf @method('DELETE')<button class="btn btn-outline-danger w-100">Eliminar equipo</button></form></div>@endcan</div>
        @endcan
    </div>
@endsection
