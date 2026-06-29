@extends('layouts.app')

@section('title', 'Equipos y selecciones')

@section('content')
<x-page-header title="Equipos y selecciones" subtitle="Catálogo único de clubes y selecciones nacionales">
    @can('create', App\Models\GameClub::class)
        <div class="d-flex gap-2">
            <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#import-clubs-modal">Importar catálogo</button>
            <a class="btn btn-primary" href="{{ route('game-clubs.create') }}">+ Nuevo equipo</a>
        </div>
    @endcan
</x-page-header>

<div class="mp-card p-3 p-lg-4">
    <form class="row g-2 mb-4" method="get">
        <div class="col-lg-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Buscar equipo o selección…"></div>
        <div class="col-lg-3"><select class="form-select" name="game"><option value="">Todos los juegos</option>@foreach($games as $game)<option value="{{ $game->value }}" @selected(request('game') === $game->value)>{{ $game->label() }}</option>@endforeach</select></div>
        <div class="col-lg-3"><select class="form-select" name="team_type"><option value="">Todos los tipos</option>@foreach($types as $type)<option value="{{ $type->value }}" @selected(request('team_type') === $type->value)>{{ $type->label() }}</option>@endforeach</select></div>
        <div class="col-lg-2"><button class="btn btn-primary w-100">Filtrar</button></div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Equipo</th><th>Tipo</th><th>Videojuegos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            @forelse($clubs as $club)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            @if($club->crestUrl())
                                <img class="mp-game-club-crest" src="{{ $club->crestUrl() }}" alt="Escudo de {{ $club->name }}" loading="lazy" referrerpolicy="no-referrer">
                            @else
                                <span class="mp-game-club-crest">{{ $club->countryFlag() ?? mb_strtoupper(mb_substr($club->name, 0, 1)) }}</span>
                            @endif
                            <div><strong>{{ $club->name }}</strong>@if($club->countryFlag())<div class="small mp-muted">{{ $club->countryFlag() }} {{ $club->country_code }}</div>@endif</div>
                        </div>
                    </td>
                    <td><span class="badge {{ $club->team_type === App\Enums\GameClubType::NationalTeam ? 'text-bg-info' : 'text-bg-secondary' }}">{{ $club->team_type->label() }}</span></td>
                    <td><div class="d-flex flex-wrap gap-1">@foreach($club->availabilities as $availability)<span class="badge mp-game-badge">{{ $availability->game->label() }}</span>@endforeach</div></td>
                    <td><span class="badge {{ $club->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $club->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                    <td><div class="d-flex justify-content-end gap-1">@can('update', $club)<a class="btn btn-sm btn-outline-primary" href="{{ route('game-clubs.edit', $club) }}">Editar</a>@endcan @can('delete', $club)<form method="post" action="{{ route('game-clubs.destroy', $club) }}" data-confirm="¿Eliminar este equipo?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Eliminar</button></form>@endcan</div></td>
                </tr>
            @empty
                <tr><td colspan="5" class="mp-empty mp-muted">No hay equipos o selecciones cargados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $clubs->links() }}
    <p class="small mp-muted mt-3 mb-0">Datos e imágenes externas proporcionados por <a href="https://www.thesportsdb.com" target="_blank" rel="noreferrer">TheSportsDB</a>.</p>
</div>

@can('create', App\Models\GameClub::class)
<div class="modal fade" id="import-clubs-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content"><form method="post" action="{{ route('game-clubs.import-popular') }}">@csrf
        <div class="modal-header"><h2 class="modal-title fs-5">Importar equipos</h2><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <h3 class="h6">Catálogos</h3>
            <div class="form-check mb-2"><input class="form-check-input" id="catalog-clubs" name="catalogs[]" type="checkbox" value="clubs" checked><label class="form-check-label" for="catalog-clubs">Clubes populares</label></div>
            <div class="form-check mb-4"><input class="form-check-input" id="catalog-national" name="catalogs[]" type="checkbox" value="national_teams" checked><label class="form-check-label" for="catalog-national">Selecciones mundialistas</label></div>
            <h3 class="h6">Videojuegos disponibles</h3>
            @foreach([App\Enums\GameType::EaSportsFc, App\Enums\GameType::Fifa, App\Enums\GameType::Pes] as $game)
                <div class="form-check mb-2"><input class="form-check-input" id="import-{{ $game->value }}" name="games[]" type="checkbox" value="{{ $game->value }}" checked><label class="form-check-label" for="import-{{ $game->value }}">{{ $game->label() }}</label></div>
            @endforeach
        </div>
        <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-success">Importar catálogo</button></div>
    </form></div></div>
</div>
@endcan
@endsection
