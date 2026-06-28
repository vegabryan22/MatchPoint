@extends('layouts.guest')
@section('title', 'Iniciar sesión')
@section('content')
    <div class="text-center mb-4"><h1 class="h3 fw-bold">Vuelve a la arena</h1><p class="text-secondary mb-0">Administra cada partido desde un solo lugar.</p></div>
    <form method="post" action="{{ route('login.store') }}" class="needs-validation" novalidate>
        @csrf
        <div class="mb-3"><label class="form-label" for="email">Correo electrónico</label><input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"><x-field-error name="email" /></div>
        <div class="mb-3"><label class="form-label" for="password">Contraseña</label><input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required autocomplete="current-password"><x-field-error name="password" /></div>
        <div class="d-flex justify-content-between align-items-center mb-4"><div class="form-check"><input type="hidden" name="remember" value="0"><input class="form-check-input" id="remember" name="remember" type="checkbox" value="1"><label class="form-check-label" for="remember">Recordarme</label></div><a href="{{ route('password.request') }}">Olvidé mi contraseña</a></div>
        <button class="btn btn-primary w-100 py-2" type="submit">Entrar a MatchPoint</button>
    </form>
@endsection
