<div class="mp-world-center" data-bracket-side="center">
    <div class="mp-world-round-title mp-world-center-title">
        <span>{{ $section['center_round']['name'] }}</span>
        <small>Partido decisivo</small>
    </div>
    <div class="mp-world-center-body">
        <div class="mp-world-cup" aria-label="Copa del torneo">
            <svg viewBox="0 0 120 140" role="img" aria-hidden="true">
                <defs>
                    <linearGradient id="matchpoint-cup" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#fff1a8"/>
                        <stop offset=".48" stop-color="#ffd166"/>
                        <stop offset="1" stop-color="#c98919"/>
                    </linearGradient>
                </defs>
                <path fill="none" stroke="url(#matchpoint-cup)" stroke-width="9" stroke-linecap="round" d="M31 23H12v18c0 22 13 35 32 37M89 23h19v18c0 22-13 35-32 37"/>
                <path fill="url(#matchpoint-cup)" d="M29 10h62v34c0 27-12 48-31 57C41 92 29 71 29 44V10Z"/>
                <path fill="url(#matchpoint-cup)" d="M52 92h16v25H52zM35 116h50l9 14H26z"/>
                <circle cx="60" cy="47" r="15" fill="#8f6418" opacity=".34"/>
                <path fill="#fff4bb" d="m60 30 5 10 11 2-8 8 2 11-10-5-10 5 2-11-8-8 11-2z"/>
            </svg>
            <div class="mp-eyebrow">Copa MatchPoint</div>
            <strong>{{ $bracketChampion['name'] ?? 'En disputa' }}</strong>
        </div>
        <div class="mp-world-final-match">
            @include('tournaments.draws._world-match', ['matchData' => $section['center_match']])
        </div>
    </div>
</div>
