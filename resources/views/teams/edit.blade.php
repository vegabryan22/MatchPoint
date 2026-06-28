@extends('layouts.app')

@section('title', 'Editar equipo')

@section('content')
    <x-page-header :title="'Editar '.$team->name" subtitle="Actualiza identidad y composición del equipo" />
    <div class="mp-card p-4">
        @if($team->logoUrl())<div class="mb-4"><img class="mp-team-logo mp-team-logo-lg" src="{{ $team->logoUrl() }}" alt="Logo actual de {{ $team->name }}"></div>@endif
        <form method="post" action="{{ route('teams.update', $team) }}" enctype="multipart/form-data" class="needs-validation" novalidate>@csrf @method('PUT') @include('teams._form', ['submitLabel' => 'Guardar cambios'])</form>
    </div>
@endsection
