@extends('layouts.public')
@section('title', 'Inscripción · '.$tournament->name)
@section('content')
<div class="mp-card p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="mp-eyebrow">Torneo rápido</div><h1 class="h2 fw-bold">{{ $tournament->name }}</h1>
        <p class="mp-muted mb-2">{{ $tournament->gameLabel() }} · {{ $availability['remaining'] }} cupos disponibles</p>
        @if($tournament->quick_registration_notice)<div class="alert alert-info text-start mb-0">{{ $tournament->quick_registration_notice }}</div>@endif
    </div>
    <x-field-error name="registration" />
    @if (! $availability['open'])
        <div class="alert alert-warning mb-0">{{ $availability['message'] }}</div>
    @else
        <form class="needs-validation" method="post" action="{{ route('quick-registrations.store', $tournament) }}" novalidate>
            @csrf
            <div class="row g-3">
                <div class="col-md-7"><label class="form-label" for="full_name">Nombre completo</label><input class="form-control" id="full_name" name="full_name" value="{{ old('full_name') }}" maxlength="120" required autofocus autocomplete="name"><x-field-error name="full_name" /></div>
                <div class="col-md-5"><label class="form-label" for="username">Nombre de usuario</label><input class="form-control" id="username" name="username" value="{{ old('username') }}" maxlength="80" required placeholder="Tu gamertag"><x-field-error name="username" /></div>
                <div class="col-md-6"><label class="form-label" for="academic_level">Nivel</label><select class="form-select" id="academic_level" name="academic_level" required><option value="">Seleccionar…</option>@foreach($tournament->quick_registration_levels as $level)<option value="{{ $level }}" @selected(old('academic_level') === $level)>{{ App\Enums\AcademicLevel::from($level)->label() }}</option>@endforeach</select><x-field-error name="academic_level" /></div>
                <div class="col-md-6"><label class="form-label" for="controller_platform">Control que llevarás</label><select class="form-select" id="controller_platform" name="controller_platform" required><option value="">Seleccionar…</option>@foreach($controllers as $controller)<option value="{{ $controller->value }}" @selected(old('controller_platform') === $controller->value)>{{ $controller->label() }}</option>@endforeach</select><x-field-error name="controller_platform" /></div>
                <div class="col-12"><div class="form-check border rounded-3 p-3 ps-5"><input class="form-check-input" id="bring_own_controller" name="bring_own_controller" type="checkbox" value="1" required @checked(old('bring_own_controller'))><label class="form-check-label fw-semibold" for="bring_own_controller">Confirmo que llevaré mi propio control PS4 o PS5, cargado y funcional.</label></div><x-field-error name="bring_own_controller" /></div>
                <div class="d-none" aria-hidden="true"><label for="website">Sitio web</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
            </div>
            <button class="btn btn-primary w-100 py-3 mt-4 fw-bold" type="submit">Confirmar inscripción</button>
            <p class="small mp-muted text-center mt-3 mb-0">No necesitas crear una cuenta ni proporcionar correo o contraseña.</p>
        </form>
    @endif
</div>
@endsection
