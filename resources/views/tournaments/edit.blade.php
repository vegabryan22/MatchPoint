@extends('layouts.app')
@section('title', 'Editar torneo')
@section('content')
    <x-page-header :title="'Editar '.$tournament->name" :subtitle="'Estado actual: '.$tournament->status->label()" />
    <x-field-error name="tournament" />
    <div class="mp-card p-4">
        <form method="post" action="{{ route('tournaments.update', $tournament) }}" class="needs-validation" novalidate>
            @csrf
            @method('PUT')
            <div class="alert alert-danger d-none" data-validation-summary>Revisa el primer campo marcado antes de guardar.</div>
            @if($errors->any())
                <div class="alert alert-danger"><strong>No se pudo guardar el torneo.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @include('tournaments._form', ['submitLabel' => 'Guardar configuración'])
        </form>
    </div>
@endsection
