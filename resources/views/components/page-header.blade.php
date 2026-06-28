@props(['title', 'subtitle' => null])
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div><h1 class="h3 fw-bold mb-1">{{ $title }}</h1>@if ($subtitle)<p class="mp-muted mb-0">{{ $subtitle }}</p>@endif</div>
    <div>{{ $slot }}</div>
</div>
