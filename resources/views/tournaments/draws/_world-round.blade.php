@php
    $matches = $round['matches'];
@endphp

<div class="mp-world-round is-{{ $side }} {{ $hasOuterRound ? 'has-outer-round' : '' }}" data-bracket-side="{{ $side }}" data-round-number="{{ $round['model']->number }}">
    <div class="mp-world-round-title">
        <span>{{ $round['name'] }}</span>
        <small>{{ count($matches) }} {{ count($matches) === 1 ? 'partido' : 'partidos' }}</small>
    </div>
    <div class="mp-world-round-matches" style="--match-count: {{ max(1, count($matches)) }}">
        @foreach($matches as $matchData)
            @include('tournaments.draws._world-match', ['matchData' => $matchData])
        @endforeach
    </div>
</div>
