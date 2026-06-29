@php($match = $matchData['model'])
<article class="mp-world-match {{ $matchData['status_class'] }} {{ $match->is_conditional ? 'is-conditional' : '' }}">
    <div class="mp-world-match-meta">
        <span>Partido {{ $match->sequence }}</span>
        <span>{{ $match->status->label() }}</span>
    </div>
    <div class="mp-world-team {{ $match->winner_id === $match->participant_a_id ? 'is-winner' : '' }}">
        <span class="mp-world-team-mark">@if(!empty($matchData['club_a']['crest']))<img src="{{ $matchData['club_a']['crest'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">@elseif(!empty($matchData['club_a']['flag'])){{ $matchData['club_a']['flag'] }}@else{{ mb_strtoupper(mb_substr($matchData['participant_a'], 0, 1)) }}@endif</span>
        <span class="mp-world-team-name">{{ $matchData['participant_a'] }}@if($matchData['club_a'])<small>{{ $matchData['club_a']['name'] }}</small>@endif</span>
        <strong class="mp-world-score">{{ $matchData['score_a'] ?? '–' }}</strong>
    </div>
    <div class="mp-world-team {{ $match->winner_id === $match->participant_b_id ? 'is-winner' : '' }}">
        <span class="mp-world-team-mark">@if(!empty($matchData['club_b']['crest']))<img src="{{ $matchData['club_b']['crest'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">@elseif(!empty($matchData['club_b']['flag'])){{ $matchData['club_b']['flag'] }}@else{{ mb_strtoupper(mb_substr($matchData['participant_b'], 0, 1)) }}@endif</span>
        <span class="mp-world-team-name">{{ $matchData['participant_b'] }}@if($matchData['club_b'])<small>{{ $matchData['club_b']['name'] }}</small>@endif</span>
        <strong class="mp-world-score">{{ $matchData['score_b'] ?? '–' }}</strong>
    </div>
    @if ($match->is_conditional)<div class="mp-world-match-note">Partido condicional</div>@endif
    @if ($tournament->status === App\Enums\TournamentStatus::InProgress && $match->participant_a_id && $match->participant_b_id && in_array($match->status, [App\Enums\MatchStatus::Pending, App\Enums\MatchStatus::Completed], true))
        @can('recordResult', $match)
            <a class="mp-world-match-action" href="{{ route('matches.results.edit', $match) }}">{{ $match->status === App\Enums\MatchStatus::Completed ? 'Corregir' : 'Registrar resultado' }} →</a>
        @endcan
    @endif
</article>
