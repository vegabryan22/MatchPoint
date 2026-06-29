@extends('layouts.public')
@php($tournament = $registration->tournament)
@section('title', 'Inscripción confirmada')
@section('content')
<div class="mp-card p-4 p-md-5 text-center">
    <div class="display-4 mb-3">✓</div><div class="mp-eyebrow">Inscripción confirmada</div><h1 class="h2 fw-bold">¡Nos vemos en el torneo!</h1>
    <p class="mp-muted">{{ $registration->player->name }} quedó inscrito en {{ $tournament->name }}.</p>
    <div class="row g-3 text-start my-4">
        <div class="col-sm-6"><div class="p-3 rounded bg-body"><small class="mp-muted d-block">Usuario</small><strong>{{ $registration->player->nickname }}</strong></div></div>
        <div class="col-sm-6"><div class="p-3 rounded bg-body"><small class="mp-muted d-block">Nivel</small><strong>{{ App\Enums\AcademicLevel::from($registration->academic_level)->label() }}</strong></div></div>
        <div class="col-sm-6"><div class="p-3 rounded bg-body"><small class="mp-muted d-block">Control</small><strong>{{ App\Enums\PlayStationController::from($registration->controller_platform)->label() }}</strong></div></div>
        <div class="col-sm-6"><div class="p-3 rounded bg-body"><small class="mp-muted d-block">Código</small><strong>{{ $registration->public_reference }}</strong></div></div>
    </div>
    <div class="alert alert-info text-start">Recuerda llevar tu propio control cargado y funcional. Guarda una captura de este comprobante.</div>
</div>
@endsection
