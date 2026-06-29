@extends('layouts.app')

@section('title', 'Competición · '.$tournament->name)

@section('content')
    <x-page-header :title="'Competición · '.$tournament->name" :subtitle="$tournament->format->label()">
        <a class="btn btn-outline-secondary" href="{{ route('tournaments.show', $tournament) }}">Volver al torneo</a>
    </x-page-header>

    <x-field-error name="groups" />
    <x-field-error name="group_count" />
    <x-field-error name="qualifiers_per_group" />

    @if ($groups->isEmpty())
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="mp-card p-4">
                    <div class="mp-eyebrow">Configuración</div>
                    <h2 class="h4 fw-bold">Generar calendario</h2>
                    @if($tournament->format === App\Enums\TournamentFormat::WorldCup48)
                        <p class="mp-muted">Formato estricto: 12 grupos de cuatro. Clasifican los dos primeros y los ocho mejores terceros.</p>
                        <div class="alert {{ $participants->count() === 48 ? 'alert-success' : 'alert-warning' }}">
                            Inscritos: <strong>{{ $participants->count() }}/48</strong>
                            @if($participants->count() < 48) · Faltan {{ 48 - $participants->count() }} participantes.@endif
                        </div>
                    @else
                        <p class="mp-muted">Los participantes se distribuyen por serpentina y cada grupo utiliza el método circular sin cruces repetidos.</p>
                    @endif
                    @can('manageGroups', $tournament)
                        <form method="post" action="{{ route('tournaments.groups.store', $tournament) }}">
                            @csrf
                            <div class="row g-3">
                                @if($tournament->format === App\Enums\TournamentFormat::WorldCup48)
                                    <input type="hidden" name="group_count" value="12">
                                    <input type="hidden" name="qualifiers_per_group" value="2">
                                    <div class="col-sm-6"><div class="mp-card p-3"><div class="small mp-muted">Grupos</div><strong>12 × 4 participantes</strong></div></div>
                                    <div class="col-sm-6"><div class="mp-card p-3"><div class="small mp-muted">Fase eliminatoria</div><strong>32 clasificados</strong></div></div>
                                @else
                                    <div class="col-sm-6">
                                        <label class="form-label" for="group_count">Cantidad de grupos</label>
                                        <input class="form-control" id="group_count" name="group_count" type="number" min="1" max="16" value="{{ old('group_count', $tournament->format === App\Enums\TournamentFormat::GroupsKnockout ? 2 : 1) }}">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="qualifiers_per_group">Clasificados por grupo</label>
                                        <input class="form-control" id="qualifiers_per_group" name="qualifiers_per_group" type="number" min="0" max="8" value="{{ old('qualifiers_per_group', $tournament->format === App\Enums\TournamentFormat::GroupsKnockout ? 2 : 0) }}">
                                    </div>
                                @endif
                                <div class="col-12"><button class="btn btn-primary" @disabled($tournament->format === App\Enums\TournamentFormat::WorldCup48 && $participants->count() !== 48)>Generar grupos y jornadas</button></div>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-secondary mb-0">Un organizador debe generar la competición.</div>
                    @endcan
                </div>
            </div>
            <div class="col-lg-5">
                <div class="mp-card p-4 h-100">
                    <h2 class="h5 fw-bold">Reglas</h2>
                    <ul class="mp-muted mb-0">
                        <li>Victoria: 3 puntos.</li>
                        <li>Empate: 1 punto.</li>
                        <li>Derrota: 0 puntos.</li>
                        <li>Desempate: puntos, diferencia y goles.</li>
                    </ul>
                </div>
            </div>
        </div>
    @else
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div class="mp-muted">{{ $groups->count() }} grupos · {{ $groups->sum(fn ($group) => $group->matches->count()) }} partidos</div>
            @if (in_array($tournament->format, [App\Enums\TournamentFormat::GroupsKnockout, App\Enums\TournamentFormat::WorldCup48], true) && $knockoutRounds->isEmpty())
                @can('manageGroups', $tournament)
                    <form method="post" action="{{ route('tournaments.groups.qualify', $tournament) }}" data-confirm="¿Confirmar posiciones y generar la fase eliminatoria?">
                        @csrf
                        <button class="btn btn-primary">Clasificar a eliminatorias</button>
                    </form>
                @endcan
            @endif
        </div>

        <div class="row g-4 mb-4">
            @foreach ($groups as $group)
                <div class="col-xl-6">
                    <section class="mp-card overflow-hidden h-100">
                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                            <div><div class="mp-eyebrow">Posiciones</div><h2 class="h5 fw-bold mb-0">{{ $group->name }}</h2></div>
                            @if ($group->qualifiers_count > 0)<span class="badge text-bg-primary">Top {{ $group->qualifiers_count }}</span>@endif
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>#</th><th>Participante</th><th>PJ</th><th>V</th><th>E</th><th>D</th><th>DG</th><th>Pts</th></tr></thead>
                                <tbody>
                                    @foreach ($standings[$group->id] as $row)
                                        <tr class="{{ $row['position'] <= $group->qualifiers_count ? 'table-success' : '' }}">
                                            <td>{{ $row['position'] }}</td><td class="fw-bold">{{ $row['name'] }}</td><td>{{ $row['played'] }}</td><td>{{ $row['wins'] }}</td><td>{{ $row['draws'] }}</td><td>{{ $row['losses'] }}</td><td>{{ $row['goal_difference'] > 0 ? '+' : '' }}{{ $row['goal_difference'] }}</td><td class="fw-bold">{{ $row['points'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            @endforeach
        </div>

        @if($tournament->format === App\Enums\TournamentFormat::WorldCup48)
            <section class="mp-card overflow-hidden mb-4">
                <div class="p-4 border-bottom"><div class="mp-eyebrow">Clasificación adicional</div><h2 class="h5 fw-bold mb-0">Ranking de mejores terceros</h2></div>
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>#</th><th>Grupo</th><th>Participante</th><th>PJ</th><th>DG</th><th>GF</th><th>Pts</th><th>Estado</th></tr></thead>
                    <tbody>@foreach($bestThirds as $row)<tr class="{{ $row['qualified_as_third'] ? 'table-success' : '' }}"><td>{{ $row['third_place_rank'] }}</td><td>{{ $row['group_name'] }}</td><td class="fw-bold">{{ $row['name'] }}</td><td>{{ $row['played'] }}</td><td>{{ $row['goal_difference'] > 0 ? '+' : '' }}{{ $row['goal_difference'] }}</td><td>{{ $row['goals_for'] }}</td><td class="fw-bold">{{ $row['points'] }}</td><td><span class="badge {{ $row['qualified_as_third'] ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $row['qualified_as_third'] ? 'Clasifica' : 'Eliminado' }}</span></td></tr>@endforeach</tbody>
                </table></div>
            </section>
        @endif

        <section class="mp-card p-4 mb-4">
            <div class="mp-eyebrow">Calendario</div>
            <h2 class="h5 fw-bold mb-4">Jornadas</h2>
            @php
                $matchdays = $groups->flatMap(fn ($group) => $group->matches)->groupBy('round_id')->sortKeys();
            @endphp
            <div class="accordion" id="matchdays">
                @foreach ($matchdays as $roundMatches)
                    @php
                        $round = $roundMatches->first()->round;
                    @endphp
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#round-{{ $round->id }}">{{ $round->name }} <span class="badge text-bg-secondary ms-2">{{ $roundMatches->count() }}</span></button></h3>
                        <div id="round-{{ $round->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#matchdays">
                            <div class="accordion-body"><div class="row g-3">
                                @foreach ($roundMatches as $match)
                                    @php
                                        $a = $participants->get($match->participant_a_id);
                                        $b = $participants->get($match->participant_b_id);
                                        $nameA = $a?->nickname ?? $a?->name ?? 'Participante';
                                        $nameB = $b?->nickname ?? $b?->name ?? 'Participante';
                                        $goalsA = $match->scores->sum('participant_a_score');
                                        $goalsB = $match->scores->sum('participant_b_score');
                                    @endphp
                                    <div class="col-md-6 col-xl-4"><div class="mp-dashboard-match h-100">
                                        <div class="d-flex justify-content-between mb-2"><span class="badge text-bg-secondary">{{ $match->group->name }}</span><span class="small mp-muted">{{ $match->status->label() }}</span></div>
                                        <div class="d-flex justify-content-between fw-bold"><span>{{ $nameA }}</span><span>{{ $match->status === App\Enums\MatchStatus::Completed ? $goalsA : '—' }}</span></div>
                                        <div class="d-flex justify-content-between fw-bold mt-2"><span>{{ $nameB }}</span><span>{{ $match->status === App\Enums\MatchStatus::Completed ? $goalsB : '—' }}</span></div>
                                        @can('recordResult', $match)
                                            @if ($tournament->status === App\Enums\TournamentStatus::InProgress)
                                                <a class="btn btn-sm btn-outline-primary w-100 mt-3" href="{{ route('matches.results.edit', $match) }}">{{ $match->status === App\Enums\MatchStatus::Completed ? 'Corregir' : 'Registrar resultado' }}</a>
                                            @endif
                                        @endcan
                                    </div></div>
                                @endforeach
                            </div></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($knockoutRounds->isNotEmpty())
            <section class="mp-card p-4">
                <div class="mp-eyebrow">Clasificación</div><h2 class="h5 fw-bold mb-3">Fase eliminatoria</h2>
                <div class="mp-bracket-scroll"><div class="mp-bracket">
                    @foreach ($knockoutRounds as $round)
                        <div class="mp-bracket-round"><div class="mp-bracket-round-title"><span>{{ $round->name }}</span></div><div class="mp-bracket-round-matches">
                            @foreach ($round->matches as $match)
                                @php
                                    $a = $participants->get($match->participant_a_id);
                                    $b = $participants->get($match->participant_b_id);
                                @endphp
                                <div class="mp-match-card"><div class="mp-match-header">Partido {{ $match->sequence }}</div><div class="mp-match-participant">{{ $a?->nickname ?? $a?->name ?? 'Por definir' }}</div><div class="mp-match-participant">{{ $b?->nickname ?? $b?->name ?? 'Por definir' }}</div></div>
                            @endforeach
                        </div></div>
                    @endforeach
                </div></div>
            </section>
        @endif
    @endif
@endsection
