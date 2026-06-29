@props(['tournament', 'publicForm'])

<section class="mp-card mp-public-form-share p-4 mb-4">
    <div class="row g-4 align-items-center">
        <div class="col-md-auto text-center">
            <img
                class="mp-public-form-qr"
                src="{{ route('public-forms.qr', [$tournament, $publicForm['type'], 'format' => 'svg', 'size' => 256]) }}"
                alt="Código QR para {{ $publicForm['type']->label() }}"
            >
        </div>
        <div class="col">
            <div class="mp-eyebrow">Publicidad e inscripción</div>
            <h2 class="h4 fw-bold">QR del formulario público</h2>
            <p class="mp-muted">Escanea para abrir {{ mb_strtolower($publicForm['type']->label()) }} de {{ $tournament->name }}.</p>
            <div class="input-group mb-3">
                <input class="form-control" value="{{ $publicForm['url'] }}" readonly aria-label="Enlace público">
                <button class="btn btn-outline-secondary" type="button" data-copy-url="{{ $publicForm['url'] }}">Copiar enlace</button>
            </div>
            @if($publicForm['is_local'])
                <div class="alert alert-warning py-2 small">Este QR usa una dirección local. Para publicidad configura `APP_URL` con el dominio HTTPS público.</div>
            @endif
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-success" href="{{ $publicForm['url'] }}" target="_blank" rel="noreferrer">Abrir formulario</a>
                <a class="btn btn-outline-primary" href="{{ route('public-forms.qr', [$tournament, $publicForm['type'], 'format' => 'png', 'size' => 1024, 'download' => 1]) }}">Descargar PNG</a>
                <a class="btn btn-outline-primary" href="{{ route('public-forms.qr', [$tournament, $publicForm['type'], 'format' => 'svg', 'size' => 1024, 'download' => 1]) }}">Descargar SVG</a>
                <a class="btn btn-outline-secondary" href="{{ route('public-forms.poster', [$tournament, $publicForm['type']]) }}" target="_blank">Imprimir afiche</a>
            </div>
        </div>
    </div>
</section>
