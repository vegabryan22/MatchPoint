@php
    $selectedGames = collect(old('games', isset($club) ? $club->availabilities->map(fn ($availability) => $availability->game->value)->all() : [App\Enums\GameType::EaSportsFc->value]));
    $selectedType = old('team_type', isset($club) ? $club->team_type->value : App\Enums\GameClubType::Club->value);
@endphp

<div class="row g-4">
    <div class="col-md-7"><label class="form-label" for="name">Nombre</label><input class="form-control" id="name" name="name" value="{{ old('name', $club->name ?? '') }}" required maxlength="120"><x-field-error name="name" /></div>
    <div class="col-md-5"><label class="form-label" for="team_type">Tipo</label><select class="form-select" id="team_type" name="team_type" required>@foreach($types as $type)<option value="{{ $type->value }}" @selected($selectedType === $type->value)>{{ $type->label() }}</option>@endforeach</select><x-field-error name="team_type" /></div>
    <div class="col-md-5"><label class="form-label" for="country_code">Código de país</label><input class="form-control text-uppercase" id="country_code" name="country_code" value="{{ old('country_code', $club->country_code ?? '') }}" minlength="2" maxlength="2" placeholder="CR"><div class="form-text">Obligatorio para selecciones. Formato ISO de dos letras.</div><x-field-error name="country_code" /></div>
    <div class="col-md-7">
        <fieldset><legend class="form-label">Videojuegos disponibles</legend><div class="d-flex flex-wrap gap-3">@foreach($games as $game)<div class="form-check"><input class="form-check-input" id="game-{{ $game->value }}" name="games[]" type="checkbox" value="{{ $game->value }}" @checked($selectedGames->contains($game->value))><label class="form-check-label" for="game-{{ $game->value }}">{{ $game->label() }}</label></div>@endforeach</div></fieldset>
        <x-field-error name="games" />
    </div>
    <div class="col-md-8"><label class="form-label" for="crest">Escudo o bandera</label><input class="form-control" id="crest" name="crest" type="file" accept="image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG o WebP. Máximo 2 MB.</div><x-field-error name="crest" /></div>
    <div class="col-12"><label class="form-label" for="crest_url">URL externa de la imagen</label><input class="form-control" id="crest_url" name="crest_url" type="url" value="{{ old('crest_url', $club->crest_url ?? '') }}" maxlength="1000" placeholder="https://..."><div class="form-text">Se usa cuando no cargas un archivo local.</div><x-field-error name="crest_url" /></div>
    <div class="col-md-4 d-flex align-items-end"><input type="hidden" name="is_active" value="0"><div class="form-check form-switch mb-2"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $club->is_active ?? true))><label class="form-check-label" for="is_active">Disponible</label></div></div>
</div>
<div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit" data-submit-button>{{ $submitLabel }}</button><a class="btn btn-outline-secondary" href="{{ route('game-clubs.index') }}">Cancelar</a></div>
