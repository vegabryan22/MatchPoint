@extends('layouts.guest')
@section('title', 'Recuperar contraseña')
@section('content')
    <h1 class="h3 fw-bold">Recuperar acceso</h1><p class="text-secondary mb-4">Enviaremos un enlace seguro a tu correo.</p>
    <form method="post" action="{{ route('password.email') }}">@csrf<div class="mb-4"><label class="form-label" for="email">Correo electrónico</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus><x-field-error name="email" /></div><button class="btn btn-primary w-100">Enviar enlace</button></form>
    <a class="d-block text-center mt-3" href="{{ route('login') }}">Volver al inicio</a>
@endsection
