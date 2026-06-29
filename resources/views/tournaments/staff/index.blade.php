@extends('layouts.app')

@section('title', 'Personal · '.$tournament->name)

@section('content')
    <x-page-header
        :title="'Personal · '.$tournament->name"
        subtitle="Organizadores y árbitros vinculados al torneo"
    >
        <a class="btn btn-outline-secondary" href="{{ route('tournaments.show', $tournament) }}">
            Volver
        </a>
    </x-page-header>

    <div class="row g-4">
        @can('manageOrganizers', $tournament)
            <div class="col-lg-6">
                <section class="mp-card p-4 h-100">
                    <h2 class="h5 fw-bold">Organizadores</h2>
                    <p class="mp-muted">El administrador puede asignar, transferir o retirar organizadores.</p>

                    <form
                        class="row g-2 mb-4"
                        method="post"
                        action="{{ route('tournaments.staff.organizers.store', $tournament) }}"
                    >
                        @csrf

                        <div class="col-12">
                            <select class="form-select" name="user_id" required>
                                <option value="">Seleccionar organizador…</option>
                                @foreach ($organizerCandidates as $candidate)
                                    <option value="{{ $candidate->id }}">
                                        {{ $candidate->name }} · {{ $candidate->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    id="is_primary"
                                    name="is_primary"
                                    type="checkbox"
                                    value="1"
                                >
                                <label class="form-check-label" for="is_primary">Organizador principal</label>
                            </div>
                            <button class="btn btn-primary" type="submit">Asignar</button>
                        </div>
                    </form>

                    @forelse ($tournament->organizers as $organizer)
                        <div class="d-flex justify-content-between align-items-center border-top py-3">
                            <div>
                                <strong>{{ $organizer->name }}</strong>
                                <div class="small mp-muted">
                                    {{ $organizer->email }}
                                    @if ($organizer->pivot->is_primary)
                                        · Principal
                                    @endif
                                </div>
                            </div>
                            <form
                                method="post"
                                action="{{ route('tournaments.staff.organizers.destroy', [$tournament, $organizer]) }}"
                                data-confirm="¿Retirar este organizador del torneo?"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Quitar</button>
                            </form>
                        </div>
                    @empty
                        <p class="mp-muted">Sin organizadores asignados.</p>
                    @endforelse
                </section>
            </div>
        @endcan

        <div class="col-lg-6">
            <section class="mp-card p-4 h-100">
                <h2 class="h5 fw-bold">Árbitros</h2>
                <p class="mp-muted">Solo podrán ingresar resultados en este torneo.</p>

                <form
                    class="d-flex gap-2 mb-4"
                    method="post"
                    action="{{ route('tournaments.staff.officials.store', $tournament) }}"
                >
                    @csrf
                    <select class="form-select" name="user_id" required>
                        <option value="">Seleccionar árbitro…</option>
                        @foreach ($refereeCandidates as $candidate)
                            <option value="{{ $candidate->id }}">
                                {{ $candidate->name }} · {{ $candidate->email }}
                            </option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary" type="submit">Asignar</button>
                </form>

                @forelse ($tournament->officials as $official)
                    <div class="d-flex justify-content-between align-items-center border-top py-3">
                        <div>
                            <strong>{{ $official->name }}</strong>
                            <div class="small mp-muted">{{ $official->email }}</div>
                        </div>
                        <form
                            method="post"
                            action="{{ route('tournaments.staff.officials.destroy', [$tournament, $official]) }}"
                            data-confirm="¿Retirar este árbitro del torneo?"
                        >
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Quitar</button>
                        </form>
                    </div>
                @empty
                    <p class="mp-muted">Sin árbitros asignados.</p>
                @endforelse
            </section>
        </div>
    </div>
@endsection
