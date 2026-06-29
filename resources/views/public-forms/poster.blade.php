@extends('layouts.public')

@section('title', 'QR · '.$tournament->name)

@section('content')
<main class="mp-public-poster">
    <div class="mp-eyebrow">MatchPoint presenta</div>
    <h1>{{ $tournament->name }}</h1>
    <p class="mp-public-poster-subtitle">{{ $tournament->gameLabel() }} · {{ $publicForm['type']->label() }}</p>
    <img
        class="mp-public-poster-qr"
        src="{{ route('public-forms.qr', [$tournament, $publicForm['type'], 'format' => 'svg', 'size' => 1024]) }}"
        alt="Código QR de inscripción"
    >
    <h2>¡Escanea e inscríbete!</h2>
    <p class="mp-public-poster-url">{{ $publicForm['url'] }}</p>
    @if($tournament->quick_registration_notice)<div class="mp-public-poster-notice">{{ $tournament->quick_registration_notice }}</div>@endif
    <button class="btn btn-primary mt-4 d-print-none" type="button" onclick="window.print()">Imprimir</button>
</main>
@endsection
