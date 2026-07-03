@extends('layouts.app')

@section('title', 'Generar sorteo · '.$tournament->name)

@section('content')
    <x-page-header :title="'Sorteo · '.$tournament->name" subtitle="Selecciona la estrategia y revisa la vista previa">
        <a class="btn btn-outline-secondary" href="{{ route('tournaments.show', $tournament) }}">Volver al torneo</a>
    </x-page-header>

    <x-field-error name="draw" />
    <x-field-error name="seeds" />
    <x-field-error name="selected_participants" />

    <div class="mp-card p-4">
        <form method="post" action="{{ route('tournaments.draws.preview', $tournament) }}" data-arrival-draw-form>
            @csrf
            <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><strong>Mesa de llegada.</strong> Marca quienes están presentes. Puedes volver aquí e incorporar nuevos jugadores mientras no existan resultados.</div>
                <span class="badge text-bg-primary" data-present-count>0 presentes</span>
            </div>
            <div class="row g-4 mb-4">
                @foreach ($methods as $method)
                    <div class="col-md-4">
                        <label class="border rounded-3 p-3 d-block h-100" for="method-{{ $method->value }}">
                            <input class="form-check-input me-2" id="method-{{ $method->value }}" name="method" type="radio" value="{{ $method->value }}" @checked(old('method', 'random') === $method->value)>
                            <strong>{{ $method->label() }}</strong>
                            <span class="d-block mp-muted small mt-2">
                                @switch($method)
                                    @case(App\Enums\DrawMethod::Random) Mezcla todos los participantes. @break
                                    @case(App\Enums\DrawMethod::Automatic) Prioriza nivel competitivo y orden alfabético. @break
                                    @case(App\Enums\DrawMethod::Manual) Arma los cruces por posición: 1 vs 2, 3 vs 4 y así sucesivamente. @break
                                @endswitch
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>

            <input type="hidden" name="avoid_rematches" value="0">
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" id="avoid_rematches" name="avoid_rematches" type="checkbox" value="1" @checked(old('avoid_rematches', true))>
                <label class="form-check-label" for="avoid_rematches">Evitar enfrentamientos históricos cuando exista alternativa</label>
            </div>

            <input type="hidden" name="manual_pairing" value="1">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><h2 class="h5 fw-bold mb-1">Llegadas y orden manual</h2><p class="mp-muted mb-0">En modo manual, las posiciones consecutivas forman cada partido.</p></div>
                <div class="btn-group"><button class="btn btn-sm btn-outline-primary" type="button" data-select-arrivals="all">Marcar todos</button><button class="btn btn-sm btn-outline-secondary" type="button" data-select-arrivals="none">Limpiar</button></div>
            </div>
            <div class="table-responsive mb-4">
                <table class="table align-middle">
                    <thead><tr><th style="width: 100px">Llegó</th><th>Participante</th><th style="width: 180px">Posición</th></tr></thead>
                    <tbody>
                    @forelse ($participants as $participant)
                        @php
                            $oldSelected = old('selected_participants');
                            $isSelected = is_array($oldSelected)
                                ? in_array((string) $participant->id, array_map('strval', $oldSelected), true)
                                : ($activeParticipantIds->isNotEmpty() ? $activeParticipantIds->contains((int) $participant->id) : true);
                        @endphp
                        <tr>
                            <td><div class="form-check form-switch"><input class="form-check-input" id="present-{{ $participant->id }}" name="selected_participants[]" type="checkbox" value="{{ $participant->id }}" data-arrival-participant @checked($isSelected)></div></td>
                            <td>
                                <label for="present-{{ $participant->id }}"><strong>{{ $tournament->participant_type === App\Enums\ParticipantType::Individual ? $participant->nickname : $participant->name }}</strong>
                                @if ($tournament->participant_type === App\Enums\ParticipantType::Individual)<div class="mp-muted small">{{ $participant->name }} · {{ $participant->level->label() }}</div>@endif
                                </label>
                            </td>
                            <td><input class="form-control" name="seeds[{{ $participant->id }}]" type="number" min="1" max="{{ $participants->count() }}" value="{{ old('seeds.'.$participant->id, $loop->iteration) }}" data-arrival-position></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="mp-empty mp-muted">No hay participantes suficientes.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="alert alert-warning d-none" data-arrival-warning>Para que todos jueguen, selecciona una cantidad par de presentes.</div>
            <button class="btn btn-primary" data-arrival-submit @disabled($participants->count() < 2)>Generar vista previa con presentes</button>
        </form>
    </div>
@endsection
