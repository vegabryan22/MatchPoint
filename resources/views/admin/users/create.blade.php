@extends('layouts.app')
@section('title', 'Nuevo usuario')
@section('content')
<x-page-header title="Nuevo usuario" subtitle="Crea una identidad y asigna sus capacidades" />
<div class="mp-card p-4"><form method="post" action="{{ route('admin.users.store') }}" class="needs-validation" novalidate>@csrf @include('admin.users._form', ['submitLabel' => 'Crear usuario'])</form></div>
@endsection
