@extends('layouts.app')
@section('title','Nuevo equipo')
@section('content')<x-page-header title="Nuevo equipo o selección" subtitle="Configura su imagen y videojuegos disponibles"/><div class="mp-card p-4"><form method="post" action="{{ route('game-clubs.store') }}" enctype="multipart/form-data" class="needs-validation" novalidate>@csrf @include('game-clubs._form',['submitLabel'=>'Crear equipo'])</form></div>@endsection
