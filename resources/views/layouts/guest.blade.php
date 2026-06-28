<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Acceso') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="mp-guest d-flex align-items-center justify-content-center py-5">
        <div class="mp-auth-card p-4 p-md-5 text-light">
            <a class="mp-brand justify-content-center p-0 mb-4" href="{{ route('login') }}">
                <span class="mp-brand-mark">M</span>
                <span>MatchPoint</span>
            </a>
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @yield('content')
        </div>
    </main>
</body>
</html>
