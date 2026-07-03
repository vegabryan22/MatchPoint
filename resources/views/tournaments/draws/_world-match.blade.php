@php
    $match = $matchData['model'];
    $mobile = $mobile ?? false;
    $instance = $instance ?? 'bracket';
@endphp
<article class="{{ $mobile ? 'mp-mobile-match' : 'mp-world-match' }} {{ $matchData['status_class'] }} {{ $match->is_conditional ? 'is-conditional' : '' }}">
    <div class="mp-world-match-meta">
        <span>Partido {{ $match->sequence }}</span>
        <span data-inline-status>{{ $match->status->label() }}</span>
    </div>
    @if ($match->scheduled_at)
        <div class="mp-world-match-note">{{ $match->scheduled_at->format('d/m H:i') }} · {{ $match->station?->name ?? 'Sin consola' }}</div>
    @endif
    <div class="mp-world-team {{ $match->winner_id === $match->participant_a_id ? 'is-winner' : '' }}" @if($matchData['participant_a_real_name']) tabindex="0" title="Nombre completo: {{ $matchData['participant_a_real_name'] }}" data-bs-toggle="tooltip" data-bs-placement="top" @endif>
        <span class="mp-world-team-mark">@if(!empty($matchData['club_a']['crest']))<img src="{{ $matchData['club_a']['crest'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">@elseif(!empty($matchData['club_a']['flag'])){{ $matchData['club_a']['flag'] }}@else{{ mb_strtoupper(mb_substr($matchData['participant_a'], 0, 1)) }}@endif</span>
        <span class="mp-world-team-name">{{ $matchData['participant_a'] }}@if($matchData['club_a'])<small>{{ $matchData['club_a']['name'] }}</small>@endif</span>
        <strong class="mp-world-score" data-inline-score-a>{{ $matchData['score_a'] ?? '–' }}</strong>
    </div>
    <div class="mp-world-team {{ $match->winner_id === $match->participant_b_id ? 'is-winner' : '' }}" @if($matchData['participant_b_real_name']) tabindex="0" title="Nombre completo: {{ $matchData['participant_b_real_name'] }}" data-bs-toggle="tooltip" data-bs-placement="top" @endif>
        <span class="mp-world-team-mark">@if(!empty($matchData['club_b']['crest']))<img src="{{ $matchData['club_b']['crest'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">@elseif(!empty($matchData['club_b']['flag'])){{ $matchData['club_b']['flag'] }}@else{{ mb_strtoupper(mb_substr($matchData['participant_b'], 0, 1)) }}@endif</span>
        <span class="mp-world-team-name">{{ $matchData['participant_b'] }}@if($matchData['club_b'])<small>{{ $matchData['club_b']['name'] }}</small>@endif</span>
        <strong class="mp-world-score" data-inline-score-b>{{ $matchData['score_b'] ?? '–' }}</strong>
    </div>
    @if($matchData['has_penalties'])<div class="mp-world-match-note">Penales: {{ $matchData['penalties_a'] }}–{{ $matchData['penalties_b'] }}</div>@endif
    @if ($match->is_conditional)<div class="mp-world-match-note">Partido condicional</div>@endif
    @if ($tournament->status === App\Enums\TournamentStatus::InProgress && $match->participant_a_id && $match->participant_b_id && in_array($match->status, [App\Enums\MatchStatus::Pending, App\Enums\MatchStatus::Completed], true))
        @can('recordResult', $match)
            @include('matches.results._quick-form', [
                'match' => $match,
                'instance' => $instance,
                'participantAName' => $matchData['participant_a'],
                'participantBName' => $matchData['participant_b'],
            ])
        @endcan
    @endif
</article>
