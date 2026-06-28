@extends('layouts.app')

@section('title', $statistics['name'])

@section('content')
    <x-page-header :title="$statistics['name']" subtitle="Ficha de rendimiento competitivo">
        <a class="btn btn-outline-secondary" href="{{ route('statistics.index', request()->query()) }}">Volver al ranking</a>
    </x-page-header>

    <div class="row g-3 mb-4">
        @foreach ([
            ['Partidos', $statistics['played']],
            ['Victorias', $statistics['wins']],
            ['Empates', $statistics['draws']],
            ['Derrotas', $statistics['losses']],
            ['Efectividad', $statistics['win_rate'].'%'],
            ['Goles a favor', $statistics['goals_for']],
            ['Goles en contra', $statistics['goals_against']],
            ['Diferencia', ($statistics['goal_difference'] > 0 ? '+' : '').$statistics['goal_difference']],
            ['Racha actual', $statistics['streak']],
        ] as [$label, $value])
            <div class="col-6 col-lg-3">
                <div class="mp-card p-4 h-100">
                    <div class="mp-muted small">{{ $label }}</div>
                    <div class="h3 fw-bold mb-0 mt-1">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mp-card overflow-hidden">
        <div class="p-4 border-bottom">
            <div class="mp-eyebrow">Cronología</div>
            <h2 class="h5 fw-bold mb-0">Partidos recientes</h2>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Fecha</th><th>Torneo</th><th>Rival</th><th>Marcador</th><th>Resultado</th></tr></thead>
                <tbody>
                    @forelse ($history as $item)
                        <tr>
                            <td>{{ $item['match']->completed_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                            <td><a href="{{ route('tournaments.show', $item['match']->tournament) }}">{{ $item['match']->tournament->name }}</a></td>
                            <td>{{ $item['opponent_name'] }}</td>
                            <td class="fw-bold">{{ $item['goals_for'] }} — {{ $item['goals_against'] }}</td>
                            <td><span class="badge {{ match ($item['result']) { 'Victoria' => 'text-bg-success', 'Empate' => 'text-bg-secondary', default => 'text-bg-danger' } }}">{{ $item['result'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="mp-empty mp-muted" colspan="5">No hay partidos finalizados para este participante.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
