@extends('layouts.app')
@section('title', 'Editar usuario')
@section('content')
<x-page-header title="Editar usuario" subtitle="Actualiza datos, estado y roles de {{ $user->name }}" />
<div class="mp-card p-4"><form method="post" action="{{ route('admin.users.update', $user) }}" class="needs-validation" novalidate>@csrf @method('PUT') @include('admin.users._form', ['submitLabel' => 'Guardar cambios'])</form></div>
@endsection
