@php
    $dateValue = static fn ($value) => $value?->format('Y-m-d H:i');
    $selectedLevels = collect(old('quick_registration_levels', $tournament->quick_registration_levels ?? []));
    $availableLevels = App\Enums\AcademicLevel::cases();
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <label class="form-label" for="name">Nombre del torneo</label>
        <input class="form-control" id="name" name="name" value="{{ old('name', $tournament->name ?? '') }}" required maxlength="160">
        <x-field-error name="name" />
    </div>
    <div class="col-lg-4">
        <label class="form-label" for="participant_type">Modalidad</label>
        <select class="form-select" id="participant_type" name="participant_type" required>
            @foreach($participantTypes as $type)<option value="{{ $type->value }}" @selected(old('participant_type', isset($tournament) ? $tournament->participant_type->value : '') === $type->value)>{{ $type->label() }}</option>@endforeach
        </select>
        <x-field-error name="participant_type" />
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Descripción</label>
        <textarea class="form-control" id="description" name="description" rows="3" maxlength="5000">{{ old('description', $tournament->description ?? '') }}</textarea>
        <x-field-error name="description" />
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="game">Juego</label>
        <select class="form-select" id="game" name="game" required>
            <option value="">Seleccionar…</option>
            @foreach($games as $game)<option value="{{ $game->value }}" @selected(old('game', isset($tournament) ? $tournament->game->value : '') === $game->value)>{{ $game->label() }}</option>@endforeach
        </select>
        <x-field-error name="game" />
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="custom_game">Otro juego</label>
        <input class="form-control" id="custom_game" name="custom_game" value="{{ old('custom_game', $tournament->custom_game ?? '') }}" maxlength="120" placeholder="Sólo si elegiste Otro">
        <x-field-error name="custom_game" />
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="max_participants">Cupos</label>
        <select class="form-select" id="max_participants" name="max_participants" required>
            @foreach($capacities as $capacity)<option value="{{ $capacity->value }}" @selected((int) old('max_participants', $tournament->max_participants ?? 16) === $capacity->value)>{{ $capacity->value }}</option>@endforeach
        </select>
        <x-field-error name="max_participants" />
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label" for="best_of">Serie</label>
        <select class="form-select" id="best_of" name="best_of" required>
            @foreach($bestOfOptions as $bestOf)<option value="{{ $bestOf->value }}" @selected((int) old('best_of', isset($tournament) ? $tournament->best_of->value : 1) === $bestOf->value)>{{ $bestOf->label() }}</option>@endforeach
        </select>
        <x-field-error name="best_of" />
    </div>
    <div class="col-md-6">
        <label class="form-label" for="format">Formato competitivo</label>
        <select class="form-select" id="format" name="format" required>
            @foreach($formats as $format)<option value="{{ $format->value }}" @selected(old('format', isset($tournament) ? $tournament->format->value : '') === $format->value)>{{ $format->label() }}</option>@endforeach
        </select>
        <x-field-error name="format" />
    </div>
</div>

<hr class="my-4">
<h2 class="h5 fw-bold mb-3">Inscripción pública rápida</h2>
<div class="row g-4">
    <div class="col-12">
        <input type="hidden" name="quick_registration_enabled" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="quick_registration_enabled" name="quick_registration_enabled" type="checkbox" value="1" @checked(old('quick_registration_enabled', $tournament->quick_registration_enabled ?? false))>
            <label class="form-check-label" for="quick_registration_enabled">Permitir inscripción pública sin cuenta</label>
        </div>
        <div class="form-text">Disponible únicamente para modalidad individual.</div>
    </div>
    <div class="col-md-5">
        <fieldset>
            <legend class="form-label">Niveles habilitados</legend>
            <div class="row g-2">
                @foreach($availableLevels as $level)
                    <div class="col-6 col-sm-4">
                        <div class="form-check mp-academic-level-option">
                            <input class="form-check-input" id="academic-level-{{ $level->value }}" name="quick_registration_levels[]" type="checkbox" value="{{ $level->value }}" @checked($selectedLevels->contains($level->value))>
                            <label class="form-check-label" for="academic-level-{{ $level->value }}">{{ $level->label() }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </fieldset>
        <div class="form-text">Marca todos los niveles académicos que podrán inscribirse.</div>
        <x-field-error name="quick_registration_levels" />
    </div>
    <div class="col-md-7">
        <label class="form-label" for="quick_registration_notice">Aviso para participantes</label>
        <textarea class="form-control" id="quick_registration_notice" name="quick_registration_notice" rows="4" maxlength="1000" placeholder="Debes llevar tu propio control PS4 o PS5.">{{ old('quick_registration_notice', $tournament->quick_registration_notice ?? 'Cada participante debe llevar su propio control PS4 o PS5, cargado y en buen estado.') }}</textarea>
        <x-field-error name="quick_registration_notice" />
    </div>
</div>

<hr class="my-4">
<h2 class="h5 fw-bold mb-3">Calendario</h2>
<div class="row g-4">
    <div class="col-md-6 col-lg-3"><label class="form-label" for="registration_starts_at">Inicio de inscripciones</label><input class="form-control js-datetime-picker" id="registration_starts_at" name="registration_starts_at" type="text" autocomplete="off" placeholder="Seleccionar fecha y hora" value="{{ old('registration_starts_at', isset($tournament) ? $dateValue($tournament->registration_starts_at) : '') }}"><x-field-error name="registration_starts_at" /></div>
    <div class="col-md-6 col-lg-3"><label class="form-label" for="registration_ends_at">Fin de inscripciones</label><input class="form-control js-datetime-picker" id="registration_ends_at" name="registration_ends_at" type="text" autocomplete="off" placeholder="Seleccionar fecha y hora" value="{{ old('registration_ends_at', isset($tournament) ? $dateValue($tournament->registration_ends_at) : '') }}"><x-field-error name="registration_ends_at" /></div>
    <div class="col-md-6 col-lg-3"><label class="form-label" for="starts_at">Inicio del torneo</label><input class="form-control js-datetime-picker" id="starts_at" name="starts_at" type="text" autocomplete="off" placeholder="Seleccionar fecha y hora" value="{{ old('starts_at', isset($tournament) ? $dateValue($tournament->starts_at) : now()->addWeek()->format('Y-m-d H:i')) }}" required><x-field-error name="starts_at" /></div>
    <div class="col-md-6 col-lg-3"><label class="form-label" for="ends_at">Final estimada</label><input class="form-control js-datetime-picker" id="ends_at" name="ends_at" type="text" autocomplete="off" placeholder="Seleccionar fecha y hora" value="{{ old('ends_at', isset($tournament) ? $dateValue($tournament->ends_at) : '') }}"><x-field-error name="ends_at" /></div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit" data-submit-button>{{ $submitLabel }}</button>
    <a class="btn btn-outline-secondary" href="{{ route('tournaments.index') }}">Cancelar</a>
</div>
