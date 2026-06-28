@php
    $selectedPlayers = array_map('intval', old('player_ids', isset($team) ? $team->players->pluck('id')->all() : []));
    $selectedCaptain = (int) old('captain_id', isset($team) ? ($team->captain()?->id ?? 0) : 0);
@endphp

<div class="row g-4">
    <div class="col-md-6">
        <label class="form-label" for="name">Nombre del equipo</label>
        <input class="form-control" id="name" name="name" value="{{ old('name', $team->name ?? '') }}" required maxlength="120">
        <x-field-error name="name" />
    </div>
    <div class="col-md-6">
        <label class="form-label" for="logo">Logo</label>
        <input class="form-control" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">JPEG, PNG o WebP. Máximo 2 MB.</div>
        <x-field-error name="logo" />
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Descripción</label>
        <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000">{{ old('description', $team->description ?? '') }}</textarea>
        <x-field-error name="description" />
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $team->is_active ?? true))>
            <label class="form-check-label" for="is_active">Equipo activo</label>
        </div>
    </div>
</div>

<hr class="my-4">

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div><h2 class="h5 fw-bold mb-1">Plantilla</h2><p class="mp-muted mb-0">Selecciona integrantes y marca un único capitán.</p></div>
    <div><label class="visually-hidden" for="roster-search">Buscar jugador</label><input class="form-control" id="roster-search" type="search" placeholder="Buscar jugador…" data-table-search="[data-player-option]"></div>
</div>

<div class="border rounded-3 overflow-hidden">
    <div class="row g-0 px-3 py-2 bg-body-tertiary small fw-semibold"><div class="col">Jugador</div><div class="col-auto">Capitán</div></div>
    @forelse ($players as $availablePlayer)
        <div class="mp-roster-option d-flex align-items-center gap-3 px-3 py-3 border-top" data-player-option>
            <input class="form-check-input mt-0" id="player-{{ $availablePlayer->id }}" name="player_ids[]" type="checkbox" value="{{ $availablePlayer->id }}" @checked(in_array($availablePlayer->id, $selectedPlayers, true))>
            <label class="d-flex align-items-center gap-2 flex-grow-1" for="player-{{ $availablePlayer->id }}">
                <span class="mp-avatar">{{ mb_strtoupper(mb_substr($availablePlayer->nickname, 0, 1)) }}</span>
                <span><strong>{{ $availablePlayer->nickname }}</strong><span class="d-block mp-muted small">{{ $availablePlayer->name }} · {{ $availablePlayer->country }}{{ $availablePlayer->is_active ? '' : ' · Inactivo' }}</span></span>
            </label>
            <div class="form-check"><input class="form-check-input" id="captain-{{ $availablePlayer->id }}" name="captain_id" type="radio" value="{{ $availablePlayer->id }}" @checked($selectedCaptain === $availablePlayer->id)><label class="visually-hidden" for="captain-{{ $availablePlayer->id }}">Capitán {{ $availablePlayer->nickname }}</label></div>
        </div>
    @empty
        <div class="mp-empty mp-muted">Crea jugadores activos antes de formar un equipo.</div>
    @endforelse
</div>
<x-field-error name="player_ids" />
<x-field-error name="captain_id" />

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
    <a class="btn btn-outline-secondary" href="{{ route('teams.index') }}">Cancelar</a>
</div>
