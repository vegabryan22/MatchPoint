@extends('layouts.app')
@section('title', 'Reportes')
@section('content')
    <x-page-header title="Centro de reportes" subtitle="Exportaciones oficiales PDF, Excel y CSV" />
    <div class="row g-4"><div class="col-lg-8"><div class="mp-card p-4">
        <form method="post" action="{{ route('reports.export') }}" class="needs-validation" novalidate>
            @csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="type">Reporte</label><select class="form-select" id="type" name="type" required>@foreach($types as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select><x-field-error name="type" /></div>
                <div class="col-md-6"><label class="form-label" for="format">Formato</label><select class="form-select" id="format" name="format" required>@foreach($formats as $format)<option value="{{ $format->value }}">{{ $format->label() }}</option>@endforeach</select><x-field-error name="format" /></div>
                <div class="col-md-8"><label class="form-label" for="tournament_id">Torneo</label><select class="form-select" id="tournament_id" name="tournament_id"><option value="">No aplica</option>@foreach($tournaments as $tournament)<option value="{{ $tournament->id }}">{{ $tournament->name }}</option>@endforeach</select><x-field-error name="tournament_id" /></div>
                <div class="col-md-4"><label class="form-label" for="participant_type">Modalidad</label><select class="form-select" id="participant_type" name="participant_type">@foreach(App\Enums\ParticipantType::cases() as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select></div>
                <div class="col-12"><button class="btn btn-primary">Generar y descargar</button></div>
            </div>
        </form>
    </div></div><div class="col-lg-4"><div class="mp-card p-4 h-100"><div class="mp-eyebrow">Formatos</div><h2 class="h5 fw-bold">Entrega profesional</h2><p class="mp-muted">PDF para impresión, XLSX para análisis y CSV UTF-8 para interoperabilidad.</p><div class="d-flex gap-2"><span class="badge text-bg-danger">PDF</span><span class="badge text-bg-success">XLSX</span><span class="badge text-bg-primary">CSV</span></div></div></div></div>
@endsection
