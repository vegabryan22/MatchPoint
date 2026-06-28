@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
    <x-page-header
        title="Configuración"
        subtitle="Parámetros operativos globales de MatchPoint"
    />

    <form method="post" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        @foreach ($settings as $group => $groupSettings)
            <section class="mp-card p-4 mb-4">
                <h2 class="h5 fw-bold text-capitalize mb-4">{{ $group }}</h2>

                <div class="row g-4">
                    @foreach ($groupSettings as $setting)
                        <div class="col-md-6">
                            <label class="form-label" for="setting-{{ $setting->id }}">
                                {{ $setting->label }}
                            </label>

                            @if ($setting->type === 'boolean')
                                <input
                                    type="hidden"
                                    name="settings[{{ $setting->key }}]"
                                    value="0"
                                >
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        id="setting-{{ $setting->id }}"
                                        name="settings[{{ $setting->key }}]"
                                        type="checkbox"
                                        value="1"
                                        @checked($setting->typedValue())
                                    >
                                </div>
                            @else
                                <input
                                    class="form-control"
                                    id="setting-{{ $setting->id }}"
                                    name="settings[{{ $setting->key }}]"
                                    type="{{ $setting->type === 'integer' ? 'number' : 'text' }}"
                                    value="{{ old('settings.'.$setting->key, $setting->value) }}"
                                >
                            @endif

                            @if ($setting->description)
                                <div class="form-text">{{ $setting->description }}</div>
                            @endif

                            <x-field-error :name="'settings.'.$setting->key" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <button class="btn btn-primary">Guardar configuración</button>
    </form>
@endsection
