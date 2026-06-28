@extends('layouts.app')
@section('title', 'Usuarios')
@section('content')
<x-page-header title="Usuarios" subtitle="Identidades, estados y roles del sistema"><a class="btn btn-primary" href="{{ route('admin.users.create') }}">+ Nuevo usuario</a></x-page-header>
<div class="mp-card p-3 p-lg-4">
    <form class="row g-2 mb-4" method="get"><div class="col-md-7 col-lg-5"><label class="visually-hidden" for="search">Buscar</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o correo…"></div><div class="col-auto"><button class="btn btn-primary">Buscar</button></div>@if(request('search'))<div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Limpiar</a></div>@endif</form>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Usuario</th><th>Roles</th><th>Estado</th><th>Último acceso</th><th class="text-end">Acciones</th></tr></thead><tbody>
    @forelse ($users as $user)<tr><td><div class="d-flex align-items-center gap-2"><span class="mp-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span><div><a class="fw-semibold" href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a><div class="mp-muted small">{{ $user->email }}</div></div></div></td><td>@foreach($user->roles as $role)<span class="badge text-bg-secondary me-1">{{ $role->name }}</span>@endforeach</td><td><span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-danger' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></td><td class="mp-muted">{{ $user->last_login_at?->diffForHumans() ?? 'Nunca' }}</td><td><div class="d-flex justify-content-end gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.edit', $user) }}">Editar</a>@can('delete', $user)<form method="post" action="{{ route('admin.users.destroy', $user) }}" data-confirm="¿Eliminar este usuario?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Eliminar</button></form>@endcan</div></td></tr>
    @empty<tr><td colspan="5" class="mp-empty mp-muted">No encontramos usuarios con esos criterios.</td></tr>@endforelse
    </tbody></table></div>{{ $users->links() }}
</div>
@endsection
