@php
    $isCorrection = $match->status === App\Enums\MatchStatus::Completed;
    $existingScores = $match->scores->keyBy('game_number');
    $formPrefix = ($instance ?? 'quick').'-match-'.$match->id;
    $formId = $formPrefix.'-result-form';
    $formAction = $isCorrection
        ? route('matches.results.update', $match)
        : ($match->best_of->value === 1 ? route('matches.results.quick-store', $match) : route('matches.results.store', $match));
@endphp

<form
    id="{{ $formId }}"
    class="mp-quick-result-form"
    method="post"
    action="{{ $formAction }}"
    data-native-result-form
    data-dirty="false"
    @if($isCorrection) data-confirm="¿Guardar esta corrección y recalcular la siguiente ronda?" @endif
    novalidate
>
    @csrf
    @if($isCorrection) @method('PUT') @endif
    <input name="batch" type="hidden" value="{{ $match->tournament_draw_id }}" form="{{ $formId }}">
    <input name="match_id" type="hidden" value="{{ $match->id }}" form="{{ $formId }}">

    <details class="mp-quick-result" @if($match->best_of->value === 1 && ! $isCorrection) open @endif>
        <summary>{{ $isCorrection ? 'Corregir marcador' : ($match->best_of->value === 1 ? 'Marcador rápido' : 'Ingresar '.$match->best_of->label()) }}</summary>
        <div class="mp-quick-games">
            @for($gameNumber = 1; $gameNumber <= $match->best_of->value; $gameNumber++)
                @php $score = $existingScores->get($gameNumber); @endphp
                <div class="mp-quick-game">
                    <span>J{{ $gameNumber }}</span>
                    @foreach(['participant_a_score' => $participantAName, 'participant_b_score' => $participantBName] as $field => $participantName)
                        @php $inputId = $formPrefix.'-'.$gameNumber.'-'.$field; @endphp
                        <div class="mp-score-stepper">
                            <button type="button" data-score-step="-1" data-score-target="{{ $inputId }}" aria-label="Restar gol a {{ $participantName }}">−</button>
                            <input
                                id="{{ $inputId }}"
                                form="{{ $formId }}"
                                name="{{ ! $isCorrection && $match->best_of->value === 1 ? ($field === 'participant_a_score' ? 'score_a' : 'score_b') : 'games['.($gameNumber - 1).']['.$field.']' }}"
                                type="number"
                                inputmode="numeric"
                                min="0"
                                max="99"
                                value="{{ ! $isCorrection && $match->best_of->value === 1 ? old($field === 'participant_a_score' ? 'score_a' : 'score_b', $score?->{$field}) : old('games.'.($gameNumber - 1).'.'.$field, $score?->{$field}) }}"
                                placeholder="0"
                                aria-label="{{ $participantName }}, juego {{ $gameNumber }}"
                                @required($gameNumber === 1)
                            >
                            <button type="button" data-score-step="1" data-score-target="{{ $inputId }}" aria-label="Sumar gol a {{ $participantName }}">+</button>
                        </div>
                    @endforeach
                </div>
                <div class="mp-quick-game">
                    <span>Pen.</span>
                    @foreach(['participant_a_penalties' => $participantAName, 'participant_b_penalties' => $participantBName] as $field => $participantName)
                        @php
                            $inputId = $formPrefix.'-'.$gameNumber.'-'.$field;
                            $quickName = $field === 'participant_a_penalties' ? 'penalties_a' : 'penalties_b';
                        @endphp
                        <div class="mp-score-stepper">
                            <button type="button" data-score-step="-1" data-score-target="{{ $inputId }}" aria-label="Restar penal a {{ $participantName }}">−</button>
                            <input id="{{ $inputId }}" form="{{ $formId }}" name="{{ ! $isCorrection && $match->best_of->value === 1 ? $quickName : 'games['.($gameNumber - 1).']['.$field.']' }}" type="number" inputmode="numeric" min="0" max="99" value="{{ ! $isCorrection && $match->best_of->value === 1 ? old($quickName, $score?->{$field}) : old('games.'.($gameNumber - 1).'.'.$field, $score?->{$field}) }}" placeholder="–" aria-label="Penales de {{ $participantName }}, juego {{ $gameNumber }}">
                            <button type="button" data-score-step="1" data-score-target="{{ $inputId }}" aria-label="Sumar penal a {{ $participantName }}">+</button>
                        </div>
                    @endforeach
                </div>
            @endfor
        </div>
        @if((int) old('match_id') === $match->id && $errors->any())
            <div class="alert alert-danger mb-2" role="alert">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        <div class="alert alert-danger d-none mb-2" data-inline-result-errors role="alert"></div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm flex-grow-1" type="submit" form="{{ $formId }}" formaction="{{ $formAction }}" formmethod="post">{{ $isCorrection ? 'Guardar corrección' : 'Guardar resultado' }}</button>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('matches.results.edit', $match) }}" aria-label="Abrir detalles del partido">⋯</a>
        </div>
    </details>
</form>
