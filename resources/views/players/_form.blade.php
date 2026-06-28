<div class="row g-4">
    <div class="col-md-6">
        <label class="form-label" for="name">Nombre completo</label>
        <input class="form-control" id="name" name="name" value="{{ old('name', $player->name ?? '') }}" required maxlength="120">
        <x-field-error name="name" />
    </div>
    <div class="col-md-6">
        <label class="form-label" for="nickname">Apodo competitivo</label>
        <input class="form-control" id="nickname" name="nickname" value="{{ old('nickname', $player->nickname ?? '') }}" required maxlength="80">
        <x-field-error name="nickname" />
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email">Correo electrónico</label>
        <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $player->email ?? '') }}" required>
        <x-field-error name="email" />
    </div>
    <div class="col-md-6">
        <label class="form-label" for="country">País</label>
        <input class="form-control" id="country" name="country" value="{{ old('country', $player->country ?? '') }}" required maxlength="100" placeholder="Costa Rica">
        <x-field-error name="country" />
    </div>
    <div class="col-md-6">
        <label class="form-label" for="preferred_controller">Control preferido</label>
        <select class="form-select" id="preferred_controller" name="preferred_controller" required>
            <option value="">Seleccionar…</option>
            @foreach ($controllers as $controller)
                <option value="{{ $controller->value }}" @selected(old('preferred_controller', isset($player) ? $player->preferred_controller->value : '') === $controller->value)>{{ $controller->label() }}</option>
            @endforeach
        </select>
        <x-field-error name="preferred_controller" />
    </div>
    <div class="col-md-6">
        <label class="form-label" for="level">Nivel</label>
        <select class="form-select" id="level" name="level" required>
            <option value="">Seleccionar…</option>
            @foreach ($levels as $level)
                <option value="{{ $level->value }}" @selected(old('level', isset($player) ? $player->level->value : '') === $level->value)>{{ $level->label() }}</option>
            @endforeach
        </select>
        <x-field-error name="level" />
    </div>
    <div class="col-md-8">
        <label class="form-label" for="photo">Foto</label>
        <input class="form-control" id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">JPEG, PNG o WebP. Máximo 2 MB.</div>
        <x-field-error name="photo" />
    </div>
    <div class="col-md-4 d-flex align-items-end pb-2">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $player->is_active ?? true))>
            <label class="form-check-label" for="is_active">Jugador activo</label>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
    <a class="btn btn-outline-secondary" href="{{ route('players.index') }}">Cancelar</a>
</div>
