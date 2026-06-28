@extends('layouts.app')
@section('title', 'Editar torneo')
@section('content')
    <x-page-header :title="'Editar '.$tournament->name" :subtitle="'Estado actual: '.$tournament->status->label()" />
    <x-field-error name="tournament" />
    <div class="mp-card p-4"><form method="post" action="{{ route('tournaments.update', $tournament) }}" class="needs-validation" novalidate>@csrf @method('PUT') @include('tournaments._form', ['submitLabel' => 'Guardar configuración'])</form></div>
@endsection
