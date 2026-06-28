@extends('layouts.app')

@section('title', 'Generar sorteo · '.$tournament->name)

@section('content')
    <x-page-header :title="'Sorteo · '.$tournament->name" subtitle="Selecciona la estrategia y revisa la vista previa">
        <a class="btn btn-outline-secondary" href="{{ route('tournaments.show', $tournament) }}">Volver al torneo</a>
    </x-page-header>

    <x-field-error name="draw" />
    <x-field-error name="seeds" />

    <div class="mp-card p-4">
        <form method="post" action="{{ route('tournaments.draws.preview', $tournament) }}">
            @csrf
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
                                    @case(App\Enums\DrawMethod::Manual) Respeta las semillas definidas abajo. @break
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

            <h2 class="h5 fw-bold">Semillas manuales</h2>
            <p class="mp-muted">Sólo se usan al seleccionar Semillas manuales. Asigna valores consecutivos del 1 al {{ $participants->count() }}.</p>
            <div class="table-responsive mb-4">
                <table class="table align-middle">
                    <thead><tr><th>Participante</th><th style="width: 180px">Semilla</th></tr></thead>
                    <tbody>
                    @forelse ($participants as $participant)
                        <tr>
                            <td>
                                <strong>{{ $tournament->participant_type === App\Enums\ParticipantType::Individual ? $participant->nickname : $participant->name }}</strong>
                                @if ($tournament->participant_type === App\Enums\ParticipantType::Individual)<div class="mp-muted small">{{ $participant->name }} · {{ $participant->level->label() }}</div>@endif
                            </td>
                            <td><input class="form-control" name="seeds[{{ $participant->id }}]" type="number" min="1" max="{{ $participants->count() }}" value="{{ old('seeds.'.$participant->id, $loop->iteration) }}"></td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="mp-empty mp-muted">No hay participantes suficientes.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <button class="btn btn-primary" @disabled($participants->count() < 2)>Generar vista previa</button>
        </form>
    </div>
@endsection
