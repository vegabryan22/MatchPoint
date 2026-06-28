@php
    $chartValues = $activityByDay === [] ? [0] : array_values($activityByDay);
    $chartMax = max(1, max($chartValues));
@endphp

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <section class="mp-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <div class="mp-eyebrow">Agenda</div>
                    <h2 class="h5 fw-bold mb-0">Próximos partidos</h2>
                </div>
                <span class="badge text-bg-secondary">{{ $upcomingMatches->count() }} visibles</span>
            </div>
            <div class="row g-3">
                @forelse ($upcomingMatches as $match)
                    @php
                        $participantA = $match->participantAResolved;
                        $participantB = $match->participantBResolved;
                        $nameA = $participantA?->nickname ?? $participantA?->name ?? 'Por definir';
                        $nameB = $participantB?->nickname ?? $participantB?->name ?? 'Por definir';
                    @endphp
                    <div class="col-md-6">
                        <article class="mp-dashboard-match h-100">
                            <div class="d-flex justify-content-between gap-2 mb-3">
                                <span class="badge text-bg-primary">{{ $match->tournament->gameLabel() }}</span>
                                <span class="mp-muted small">{{ $match->scheduled_at?->format('d/m H:i') ?? 'Por programar' }}</span>
                            </div>
                            <div class="fw-bold">{{ $nameA }}</div>
                            <div class="mp-muted small my-1">contra</div>
                            <div class="fw-bold">{{ $nameB }}</div>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <a class="small" href="{{ route('tournaments.draws.show', $match->tournament) }}">{{ $match->tournament->name }}</a>
                                @can('recordResult', $match)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('matches.results.edit', $match) }}">Resultado</a>
                                @endcan
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12"><div class="mp-empty mp-muted">No hay partidos pendientes con ambos participantes definidos.</div></div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="col-xl-4">
        <section class="mp-card p-4 h-100">
            <div class="mp-eyebrow">Últimos 7 días</div>
            <h2 class="h5 fw-bold mb-4">Partidos finalizados</h2>
            <div class="mp-mini-chart" role="img" aria-label="Partidos finalizados durante los últimos siete días">
                @foreach ($activityByDay as $label => $count)
                    <div class="mp-mini-chart-item">
                        <div class="mp-mini-chart-value">{{ $count }}</div>
                        <div class="mp-mini-chart-track">
                            <div class="mp-mini-chart-bar" style="height: {{ max(5, ($count / $chartMax) * 100) }}%"></div>
                        </div>
                        <div class="mp-mini-chart-label">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
            <div class="mp-muted small mt-3">Actualizado {{ $generatedAt->format('H:i:s') }}</div>
        </section>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <section class="mp-card overflow-hidden h-100">
            <div class="p-4 border-bottom">
                <div class="mp-eyebrow">Resultados</div>
                <h2 class="h5 fw-bold mb-0">Partidos recientes</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Partido</th><th>Marcador</th><th>Ganador</th></tr></thead>
                    <tbody>
                        @forelse ($recentResults as $match)
                            @php
                                $participantA = $match->participantAResolved;
                                $participantB = $match->participantBResolved;
                                $nameA = $participantA?->nickname ?? $participantA?->name ?? 'Participante';
                                $nameB = $participantB?->nickname ?? $participantB?->name ?? 'Participante';
                                $goalsA = $match->scores->sum('participant_a_score');
                                $goalsB = $match->scores->sum('participant_b_score');
                                $winner = $match->winner_id === null ? 'Empate' : ($match->winner_id === $match->participant_a_id ? $nameA : $nameB);
                            @endphp
                            <tr>
                                <td><strong>{{ $nameA }} vs {{ $nameB }}</strong><div class="mp-muted small">{{ $match->tournament->name }}</div></td>
                                <td class="fw-bold">{{ $goalsA }} — {{ $goalsB }}</td>
                                <td><span class="badge text-bg-success">{{ $winner }}</span></td>
                            </tr>
                        @empty
                            <tr><td class="mp-empty mp-muted" colspan="3">Todavía no hay resultados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="mp-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div><div class="mp-eyebrow">Hall of fame</div><h2 class="h5 fw-bold mb-0">Últimos campeones</h2></div>
                <a class="small" href="{{ route('champions.index') }}">Ver todos</a>
            </div>
            <div class="d-grid gap-2">
                @forelse ($recentChampions as $champion)
                    @php($participant = $champion->resolvedParticipant)
                    <div class="mp-dashboard-champion">
                        <span class="mp-dashboard-crown">♛</span>
                        <div class="flex-grow-1">
                            <strong>{{ $participant?->nickname ?? $participant?->name ?? 'Participante eliminado' }}</strong>
                            <div class="mp-muted small">{{ $champion->tournament->name }}</div>
                        </div>
                        <small class="mp-muted">{{ $champion->crowned_at->format('d/m/Y') }}</small>
                    </div>
                @empty
                    <div class="mp-empty mp-muted">Aún no hay campeones registrados.</div>
                @endforelse
            </div>
        </section>

        @if ($recentActivity->isNotEmpty())
            <section class="mp-card p-4">
                <div class="mp-eyebrow">Administración</div>
                <h2 class="h5 fw-bold mb-3">Actividad reciente</h2>
                <div class="d-grid gap-2">
                    @foreach ($recentActivity as $log)
                        <div class="d-flex justify-content-between gap-3 small">
                            <div><span class="badge text-bg-secondary">{{ $log->action }}</span> {{ $log->user?->name ?? 'Sistema' }}</div>
                            <span class="mp-muted text-nowrap">{{ $log->created_at->diffForHumans(short: true) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
