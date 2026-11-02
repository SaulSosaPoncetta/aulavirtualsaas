{{-- Un evento de agenda (lista) --}}
@php
    $iconos = ['tarea' => 'bi-clipboard-check', 'cierre' => 'bi-lock', 'apertura' => 'bi-unlock', 'clase' => 'bi-journal-text'];
@endphp
<a href="{{ $e['url'] }}" class="d-flex align-items-center gap-2 py-2 border-bottom text-decoration-none text-reset {{ $e['hecho'] ? 'opacity-50' : '' }}">
    <span class="punto-aula" style="background-color: {{ $e['color'] }}"></span>
    <i class="bi {{ $iconos[$e['tipo']] ?? 'bi-dot' }} text-muted"></i>
    <div class="flex-grow-1 min-w-0">
        <div class="fw-semibold text-truncate {{ $e['hecho'] ? 'text-decoration-line-through' : '' }}">{{ $e['titulo'] }}</div>
        <div class="small text-muted text-truncate">
            {{ $e['aula'] }}
            @if ($e['oculto']) · <i class="bi bi-eye-slash"></i> oculta @endif
        </div>
    </div>
    <span class="small text-muted text-nowrap">{{ ucfirst($e['fecha']->locale('es')->translatedFormat('D d/m')) }} {{ $e['fecha']->format('H:i') }}</span>
    @if ($e['hecho'])<i class="bi bi-check-circle-fill text-success"></i>@endif
</a>
