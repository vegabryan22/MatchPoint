@if($qualificationProgress)
    <div class="alert {{ $qualificationProgress['completed'] === $qualificationProgress['total'] ? 'alert-success' : 'alert-info' }} mb-3">
        <strong>Clasificatoria: {{ $qualificationProgress['completed'] }}/{{ $qualificationProgress['total'] }} resultados.</strong>
        @if($qualificationProgress['completed'] < $qualificationProgress['total'])
            La llave principal se completará automáticamente al finalizar todos los partidos y seleccionar {{ $qualificationProgress['best_loser_count'] }} mejores perdedores.
        @else
            Ganadores y mejores perdedores ya fueron colocados en la llave principal.
        @endif
    </div>
@endif

<div class="mp-desktop-bracket">
@foreach($bracketSections as $section)
    <section class="mp-world-section">
        <header class="mp-world-section-header">
            <div><div class="mp-eyebrow">{{ $section['label'] === 'Fase clasificatoria' ? 'Acceso a la llave principal' : 'Cuadro competitivo' }}</div><h2>{{ $section['label'] }}</h2></div>
            <span>{{ $section['match_count'] }} partidos</span>
        </header>
        <div class="mp-world-scroll" data-bracket-scroll>
            @if($section['layout'] === 'symmetric')
                <div class="mp-world-bracket is-symmetric {{ $section['left_rounds'] !== [] ? 'has-side-rounds' : '' }}" data-bracket-canvas>
                    @foreach($section['left_rounds'] as $round)
                        @include('tournaments.draws._world-round', [
                            'round' => $round,
                            'side' => 'left',
                            'hasOuterRound' => ! $loop->first,
                        ])
                    @endforeach

                    @include('tournaments.draws._world-center', [
                        'section' => $section,
                        'bracketChampion' => $bracketChampion,
                    ])

                    @foreach($section['right_rounds'] as $round)
                        @include('tournaments.draws._world-round', [
                            'round' => $round,
                            'side' => 'right',
                            'hasOuterRound' => ! $loop->last,
                        ])
                    @endforeach
                </div>
            @else
                <div class="mp-world-bracket is-linear" data-bracket-canvas>
                    @foreach($section['rounds'] as $round)
                        <div class="mp-world-round is-linear {{ $round['is_last'] ? 'is-final-round' : '' }}">
                            <div class="mp-world-round-title"><span>{{ $round['name'] }}</span><small>{{ count($round['matches']) }} partidos</small></div>
                            <div class="mp-world-round-matches" style="--match-count: {{ max(1, count($round['matches'])) }}">
                                @foreach($round['matches'] as $matchData)
                                    @include('tournaments.draws._world-match', ['matchData' => $matchData])
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endforeach
</div>

<div class="mp-mobile-results">
    <div class="mp-mobile-results-heading">
        <div><div class="mp-eyebrow">Modo árbitro</div><h2>Ingreso rápido de marcadores</h2></div>
        <span>Actualización en vivo</span>
    </div>
    @foreach($bracketSections as $sectionIndex => $section)
        @foreach($section['rounds'] as $roundIndex => $round)
            @php
                $visibleMatches = collect($round['matches'])->filter(fn ($matchData) => $matchData['model']->participant_a_id && $matchData['model']->participant_b_id);
            @endphp
            @if($visibleMatches->isNotEmpty())
                <section class="mp-mobile-round">
                    <div class="mp-mobile-round-title"><h3>{{ $round['name'] }}</h3><span>{{ $visibleMatches->count() }} partidos</span></div>
                    <div class="mp-mobile-match-list">
                        @foreach($visibleMatches as $matchData)
                            @include('tournaments.draws._world-match', [
                                'matchData' => $matchData,
                                'mobile' => true,
                                'instance' => 'mobile-'.$sectionIndex.'-'.$roundIndex,
                            ])
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    @endforeach
</div>
