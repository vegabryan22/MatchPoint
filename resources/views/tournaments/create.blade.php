@extends('layouts.app')
@section('title', 'Nuevo torneo')
@section('content')
    <x-page-header title="Nuevo torneo" subtitle="Define la estructura competitiva y el calendario" />
    <div class="mp-card p-4"><form method="post" action="{{ route('tournaments.store') }}" class="needs-validation" novalidate>@csrf @include('tournaments._form', ['submitLabel' => 'Crear borrador'])</form></div>
@endsection
