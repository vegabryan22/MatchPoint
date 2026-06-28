@extends('layouts.app')
@section('title', 'Mi perfil')
@section('content')
<x-page-header title="Mi perfil" subtitle="Gestiona tu identidad y credenciales" />
<div class="row g-4">
    <div class="col-lg-6"><div class="mp-card p-4"><h2 class="h5 fw-bold mb-4">Información personal</h2><form method="post" action="{{ route('profile.update') }}">@csrf @method('PUT')<div class="mb-3"><label class="form-label" for="name">Nombre</label><input class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required><x-field-error name="name" /></div><div class="mb-4"><label class="form-label" for="email">Correo</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required><x-field-error name="email" /></div><button class="btn btn-primary">Guardar perfil</button></form></div></div>
    <div class="col-lg-6"><div class="mp-card p-4"><h2 class="h5 fw-bold mb-4">Cambiar contraseña</h2><form method="post" action="{{ route('profile.password') }}">@csrf @method('PUT')<div class="mb-3"><label class="form-label" for="current_password">Contraseña actual</label><input class="form-control" id="current_password" name="current_password" type="password" required><x-field-error name="current_password" /></div><div class="mb-3"><label class="form-label" for="password">Nueva contraseña</label><input class="form-control" id="password" name="password" type="password" required><x-field-error name="password" /></div><div class="mb-4"><label class="form-label" for="password_confirmation">Confirmación</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required></div><button class="btn btn-primary">Actualizar contraseña</button></form></div></div>
</div>
@endsection
