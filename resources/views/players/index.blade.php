@extends('layouts.app')

@section('title', 'Jugadores')

@section('content')
    <x-page-header title="Jugadores" subtitle="Perfiles competitivos disponibles para torneos">
        @can('create', App\Models\Player::class)
            <a class="btn btn-primary" href="{{ route('players.create') }}">+ Nuevo jugador</a>
        @endcan
    </x-page-header>

    <div class="mp-card p-3 p-lg-4">
        <form class="row g-2 mb-4" method="get">
            <div class="col-lg-4">
                <label class="visually-hidden" for="search">Buscar</label>
                <input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Nombre, apodo o correo…">
            </div>
            <div class="col-sm-4 col-lg-2">
                <select class="form-select" name="country" aria-label="País">
                    <option value="">Todos los países</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country }}" @selected(request('country') === $country)>{{ $country }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4 col-lg-2">
                <select class="form-select" name="level" aria-label="Nivel">
                    <option value="">Todos los niveles</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->value }}" @selected(request('level') === $level->value)>{{ $level->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4 col-lg-2">
                <select class="form-select" name="is_active" aria-label="Estado">
                    <option value="">Todos los estados</option>
                    <option value="1" @selected(request('is_active') === '1')>Activos</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactivos</option>
                </select>
            </div>
            <div class="col-6 col-lg-1"><button class="btn btn-primary w-100">Filtrar</button></div>
            <div class="col-6 col-lg-1"><a class="btn btn-outline-secondary w-100" href="{{ route('players.index') }}" aria-label="Limpiar filtros">×</a></div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Jugador</th><th>País</th><th>Control</th><th>Nivel</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @forelse ($players as $player)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($player->photoUrl())
                                    <img class="mp-player-photo" src="{{ $player->photoUrl() }}" alt="Foto de {{ $player->nickname }}">
                                @else
                                    <span class="mp-player-photo mp-player-placeholder">{{ mb_strtoupper(mb_substr($player->nickname, 0, 1)) }}</span>
                                @endif
                                <div><a class="fw-semibold" href="{{ route('players.show', $player) }}">{{ $player->nickname }}</a><div class="mp-muted small">{{ $player->name }}</div></div>
                            </div>
                        </td>
                        <td>{{ $player->country }}</td>
                        <td>{{ $player->preferred_controller->label() }}</td>
                        <td><span class="badge text-bg-secondary">{{ $player->level->label() }}</span></td>
                        <td><span class="badge {{ $player->is_active ? 'text-bg-success' : 'text-bg-danger' }}">{{ $player->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                        <td>
                            <div class="d-flex justify-content-end gap-1">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('players.show', $player) }}">Ver</a>
                                @can('update', $player)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('players.edit', $player) }}">Editar</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="mp-empty mp-muted">No encontramos jugadores con esos criterios.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $players->links() }}
    </div>
@endsection
