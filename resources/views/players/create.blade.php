@extends('layouts.app')

@section('title', 'Nuevo jugador')

@section('content')
    <x-page-header title="Nuevo jugador" subtitle="Registra una nueva identidad competitiva" />

    <div class="mp-card p-4">
        <form method="post" action="{{ route('players.store') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
            @csrf
            @include('players._form', ['submitLabel' => 'Crear jugador'])
        </form>
    </div>
@endsection
