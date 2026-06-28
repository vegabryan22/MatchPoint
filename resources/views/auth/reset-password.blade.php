@extends('layouts.guest')
@section('title', 'Nueva contraseña')
@section('content')
    <h1 class="h3 fw-bold">Define tu contraseña</h1><p class="text-secondary mb-4">Usa al menos 10 caracteres, mayúsculas, números y símbolos.</p>
    <form method="post" action="{{ route('password.update') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><div class="mb-3"><label class="form-label" for="email">Correo</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email', $email) }}" required><x-field-error name="email" /></div><div class="mb-3"><label class="form-label" for="password">Nueva contraseña</label><input class="form-control" id="password" name="password" type="password" required><x-field-error name="password" /></div><div class="mb-4"><label class="form-label" for="password_confirmation">Confirmar contraseña</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required></div><button class="btn btn-primary w-100">Restablecer contraseña</button></form>
@endsection
