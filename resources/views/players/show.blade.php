@extends('layouts.app')

@section('title', $player->nickname)

@section('content')
    <x-page-header :title="$player->nickname" :subtitle="$player->name">
        @can('update', $player)
            <a class="btn btn-primary" href="{{ route('players.edit', $player) }}">Editar jugador</a>
        @endcan
    </x-page-header>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="mp-card p-4">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-4 mb-4">
                    @if ($player->photoUrl())
                        <img class="mp-player-photo mp-player-photo-lg" src="{{ $player->photoUrl() }}" alt="Foto de {{ $player->nickname }}">
                    @else
                        <span class="mp-player-photo mp-player-photo-lg mp-player-placeholder">{{ mb_strtoupper(mb_substr($player->nickname, 0, 1)) }}</span>
                    @endif
                    <div>
                        <span class="badge {{ $player->is_active ? 'text-bg-success' : 'text-bg-danger' }} mb-2">{{ $player->is_active ? 'Disponible' : 'Inactivo' }}</span>
                        <h2 class="h3 fw-bold mb-1">{{ $player->nickname }}</h2>
                        <div class="mp-muted">{{ $player->email }}</div>
                    </div>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Nombre</dt><dd class="col-sm-8">{{ $player->name }}</dd>
                    <dt class="col-sm-4">País</dt><dd class="col-sm-8">{{ $player->country }}</dd>
                    <dt class="col-sm-4">Control preferido</dt><dd class="col-sm-8">{{ $player->preferred_controller->label() }}</dd>
                    <dt class="col-sm-4">Nivel competitivo</dt><dd class="col-sm-8">{{ $player->level->label() }}</dd>
                    <dt class="col-sm-4">Registrado</dt><dd class="col-sm-8">{{ $player->created_at->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>
        @can('update', $player)
            <div class="col-lg-4">
                <div class="mp-card p-4 mb-4">
                    <h2 class="h5 fw-bold">Disponibilidad</h2>
                    <p class="mp-muted">Los jugadores inactivos no podrán inscribirse en nuevos torneos.</p>
                    <form method="post" action="{{ route('players.status', $player) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn {{ $player->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} w-100">{{ $player->is_active ? 'Desactivar' : 'Activar' }}</button>
                    </form>
                </div>
                @can('delete', $player)
                    <div class="mp-card p-4 border-danger">
                        <h2 class="h5 fw-bold text-danger">Zona de riesgo</h2>
                        <p class="mp-muted">La eliminación también borra su foto almacenada.</p>
                        <form method="post" action="{{ route('players.destroy', $player) }}" data-confirm="¿Eliminar definitivamente a {{ $player->nickname }}?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger w-100">Eliminar jugador</button>
                        </form>
                    </div>
                @endcan
            </div>
        @endcan
    </div>
@endsection
