@extends('layouts.app')

@section('title', 'Nuevo equipo')

@section('content')
    <x-page-header title="Nuevo equipo" subtitle="Define identidad, estado y plantilla" />
    <div class="mp-card p-4"><form method="post" action="{{ route('teams.store') }}" enctype="multipart/form-data" class="needs-validation" novalidate>@csrf @include('teams._form', ['submitLabel' => 'Crear equipo'])</form></div>
@endsection
