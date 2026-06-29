<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inscripción') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body><main class="mp-guest py-4 py-md-5"><div class="container" style="max-width: 760px">
    <a class="mp-brand justify-content-center p-0 mb-4" href="{{ route('quick-registrations.create', $tournament ?? $registration->tournament) }}"><span class="mp-brand-mark">M</span><span>MatchPoint</span></a>
    @yield('content')
</div></main></body>
</html>
