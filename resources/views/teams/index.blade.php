@extends('layouts.app')

@section('title', 'Equipos')

@section('content')
    <x-page-header title="Equipos" subtitle="Organizaciones y plantillas competitivas">
        @can('create', App\Models\Team::class)
            <a class="btn btn-primary" href="{{ route('teams.create') }}">+ Nuevo equipo</a>
        @endcan
    </x-page-header>

    <div class="mp-card p-3 p-lg-4">
        <form class="row g-2 mb-4" method="get">
            <div class="col-md-6"><label class="visually-hidden" for="search">Buscar</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre…"></div>
            <div class="col-md-3"><select class="form-select" name="is_active"><option value="">Todos los estados</option><option value="1" @selected(request('is_active') === '1')>Activos</option><option value="0" @selected(request('is_active') === '0')>Inactivos</option></select></div>
            <div class="col-6 col-md-2"><button class="btn btn-primary w-100">Filtrar</button></div>
            <div class="col-6 col-md-1"><a class="btn btn-outline-secondary w-100" href="{{ route('teams.index') }}">×</a></div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Equipo</th><th>Integrantes</th><th>Estado</th><th>Creado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @forelse ($teams as $team)
                    <tr>
                        <td><div class="d-flex align-items-center gap-3">@if($team->logoUrl())<img class="mp-team-logo" src="{{ $team->logoUrl() }}" alt="Logo de {{ $team->name }}">@else<span class="mp-team-logo">{{ mb_strtoupper(mb_substr($team->name, 0, 1)) }}</span>@endif<div><a class="fw-semibold" href="{{ route('teams.show', $team) }}">{{ $team->name }}</a><div class="mp-muted small text-truncate" style="max-width: 340px">{{ $team->description ?: 'Sin descripción' }}</div></div></div></td>
                        <td>{{ $team->players_count }}</td>
                        <td><span class="badge {{ $team->is_active ? 'text-bg-success' : 'text-bg-danger' }}">{{ $team->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                        <td class="mp-muted">{{ $team->created_at->format('d/m/Y') }}</td>
                        <td><div class="d-flex justify-content-end gap-1"><a class="btn btn-sm btn-outline-secondary" href="{{ route('teams.show', $team) }}">Ver</a>@can('update', $team)<a class="btn btn-sm btn-outline-primary" href="{{ route('teams.edit', $team) }}">Editar</a>@endcan</div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="mp-empty mp-muted">No encontramos equipos con esos criterios.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $teams->links() }}
    </div>
@endsection
