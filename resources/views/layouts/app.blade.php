<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('matchpoint-theme') || 'dark');</script>
    <title>@yield('title', 'Panel') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="mp-shell">
    <aside class="mp-sidebar">
        <a class="mp-brand" href="{{ route('dashboard') }}">
            <span class="mp-brand-mark">M</span><span>MatchPoint</span>
        </a>
        <div class="mp-nav-label">Competición</div>
        <nav class="nav flex-column">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>◈</span> Dashboard</a>
            <a class="nav-link {{ request()->routeIs('players.*') ? 'active' : '' }}" href="{{ route('players.index') }}"><span>♙</span> Jugadores</a>
            <a class="nav-link {{ request()->routeIs('teams.*') ? 'active' : '' }}" href="{{ route('teams.index') }}"><span>⬡</span> Equipos</a>
            <a class="nav-link {{ request()->routeIs('game-clubs.*') ? 'active' : '' }}" href="{{ route('game-clubs.index') }}"><span>⚽</span> Equipos y selecciones</a>
            <a class="nav-link {{ request()->routeIs('tournaments.*') ? 'active' : '' }}" href="{{ route('tournaments.index') }}"><span>◇</span> Torneos</a>
            <a class="nav-link {{ request()->routeIs('statistics.*') ? 'active' : '' }}" href="{{ route('statistics.index') }}"><span>↗</span> Estadísticas</a>
            <a class="nav-link {{ request()->routeIs('champions.*') ? 'active' : '' }}" href="{{ route('champions.index') }}"><span>♛</span> Campeones</a>
            @can('exportReports')<a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><span>⇩</span> Reportes</a>@endcan
        </nav>
        @if (auth()->user()->isAdministrator())
            <div class="mp-nav-label">Administración</div>
            <nav class="nav flex-column">
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><span>◎</span> Usuarios</a>
                <a class="nav-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}" href="{{ route('admin.audit.index') }}"><span>⌁</span> Auditoría</a>
                <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}"><span>⚙</span> Configuración</a>
            </nav>
        @endif
    </aside>

    <div class="mp-main">
        <header class="mp-topbar d-flex align-items-center justify-content-between px-3 px-lg-4">
            <button class="btn btn-outline-secondary d-lg-none" type="button" data-sidebar-toggle aria-label="Abrir menú">☰</button>
            <div class="d-none d-lg-block mp-muted small">Centro de operaciones competitivas</div>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <button class="btn btn-outline-secondary" type="button" data-theme-toggle aria-label="Cambiar tema">◐</button>
                <a class="btn btn-outline-secondary position-relative" href="{{ route('notifications.index') }}" aria-label="Notificaciones">●@if(auth()->user()->unreadNotifications()->exists())<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger">{{ auth()->user()->unreadNotifications()->count() }}</span>@endif</a>
                <div class="dropdown">
                    <button class="btn border-0 dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                        <span class="mp-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Mi perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="post">@csrf<button class="dropdown-item text-danger">Cerrar sesión</button></form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        <main class="mp-content">
            @yield('content')
        </main>
    </div>
</div>

@if (session('success'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast text-bg-success border-0" role="status" data-bs-delay="4500">
            <div class="d-flex"><div class="toast-body">{{ session('success') }}</div><button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
        </div>
    </div>
@endif
@stack('scripts')
</body>
</html>
