@extends('layouts.app')
@section('title', 'Nuevo torneo')
@section('content')
    <x-page-header title="Nuevo torneo" subtitle="Define la estructura competitiva y el calendario" />
    <div class="mp-card p-4"><form method="post" action="{{ route('tournaments.store') }}" class="needs-validation" novalidate>@csrf
        <div class="alert alert-danger d-none" data-validation-summary>Revisa el primer campo marcado antes de crear el torneo.</div>
        @if($errors->any())<div class="alert alert-danger"><strong>No se pudo crear el torneo.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @include('tournaments._form', ['submitLabel' => 'Crear borrador'])
    </form></div>
@endsection
