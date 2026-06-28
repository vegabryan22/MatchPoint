@extends('layouts.app')

@section('title', 'Editar jugador')

@section('content')
    <x-page-header :title="'Editar '.$player->nickname" subtitle="Actualiza su perfil competitivo y disponibilidad" />

    <div class="mp-card p-4">
        @if ($player->photoUrl())
            <div class="mb-4"><img class="mp-player-photo mp-player-photo-lg" src="{{ $player->photoUrl() }}" alt="Foto actual de {{ $player->nickname }}"></div>
        @endif
        <form method="post" action="{{ route('players.update', $player) }}" enctype="multipart/form-data" class="needs-validation" novalidate>
            @csrf
            @method('PUT')
            @include('players._form', ['submitLabel' => 'Guardar cambios'])
        </form>
    </div>
@endsection
