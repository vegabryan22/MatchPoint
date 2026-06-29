@extends('layouts.app')
@section('title','Editar equipo')
@section('content')<x-page-header :title="'Editar '.$club->name" subtitle="Actualiza tipo, país, videojuegos o imagen"/><div class="mp-card p-4">@if($club->crestUrl())<img class="mp-game-club-crest mp-game-club-crest-lg mb-4" src="{{ $club->crestUrl() }}" alt="Imagen actual">@endif<form method="post" action="{{ route('game-clubs.update',$club) }}" enctype="multipart/form-data" class="needs-validation" novalidate>@csrf @method('PUT') @include('game-clubs._form',['submitLabel'=>'Guardar cambios'])</form></div>@endsection
