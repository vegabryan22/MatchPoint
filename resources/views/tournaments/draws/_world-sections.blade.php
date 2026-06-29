@foreach($bracketSections as $section)
    <section class="mp-world-section">
        <header class="mp-world-section-header">
            <div><div class="mp-eyebrow">Cuadro competitivo</div><h2>{{ $section['label'] }}</h2></div>
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
