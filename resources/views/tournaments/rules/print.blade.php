<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reglamento · {{ $tournament->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background: #eef1f6; color: #111827; }
        .rules-sheet { width: min(900px, calc(100% - 2rem)); margin: 2rem auto; background: white; padding: 3rem; box-shadow: 0 16px 40px rgba(15, 23, 42, .14); }
        .rules-header { border-bottom: 4px solid #6f4bf2; padding-bottom: 1.5rem; margin-bottom: 2rem; }
        .rules-meta { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; margin-top: 1.25rem; }
        .rules-meta div { border: 1px solid #dbe1ea; border-radius: .6rem; padding: .75rem; }
        .rules-section { break-inside: avoid; margin-bottom: 1.5rem; }
        .rules-section h2 { font-size: 1.15rem; border-left: 4px solid #6f4bf2; padding-left: .75rem; }
        .rules-section li { margin-bottom: .45rem; }
        @media (max-width: 700px) { .rules-sheet { padding: 1.25rem; } .rules-meta { grid-template-columns: repeat(2, 1fr); } }
        @media print { body { background: white; } .rules-actions { display: none !important; } .rules-sheet { width: 100%; margin: 0; padding: 0; box-shadow: none; } @page { size: A4; margin: 14mm; } }
    </style>
</head>
<body>
    <div class="rules-actions d-flex justify-content-center gap-2 py-3">
        <button class="btn btn-primary" type="button" onclick="window.print()">Imprimir reglamento</button>
        <a class="btn btn-outline-secondary" href="{{ route('tournaments.show', $tournament) }}">Volver al torneo</a>
    </div>

    <main class="rules-sheet">
        <header class="rules-header">
            <div class="text-uppercase fw-bold text-primary">MatchPoint · Reglamento oficial</div>
            <h1 class="display-6 fw-bold mt-2">{{ $tournament->name }}</h1>
            <p class="mb-0">{{ $tournament->gameLabel() }} · {{ $tournament->format->label() }} · {{ $tournament->best_of->label() }}</p>
            <div class="rules-meta">
                <div><small>Inscritos</small><strong class="d-block">{{ $participantCount }}</strong></div>
                <div><small>Clasificatorios</small><strong class="d-block">{{ $qualifyingMatches }}</strong></div>
                <div><small>Mejores perdedores</small><strong class="d-block">{{ $bestLoserCount }}</strong></div>
                <div><small>Llave principal</small><strong class="d-block">{{ $mainBracketSize }}</strong></div>
            </div>
        </header>

        @foreach($sections as $section)
            <section class="rules-section">
                <h2 class="fw-bold">{{ $section['title'] }}</h2>
                <ul>
                    @foreach($section['rules'] as $rule)<li>{{ $rule }}</li>@endforeach
                </ul>
            </section>
        @endforeach

        <footer class="border-top pt-3 mt-4 small">
            Documento generado el {{ now()->format('d/m/Y H:i') }}. La organización puede resolver situaciones no contempladas preservando la equidad competitiva.
        </footer>
    </main>
</body>
</html>
