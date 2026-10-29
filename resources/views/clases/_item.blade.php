{{-- Fila de una clase en el listado del aula --}}
<a href="{{ route('clases.show', $clase) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
    <i class="bi bi-journal-text fs-4 text-primary"></i>
    <div class="flex-grow-1 min-w-0">
        <div class="fw-semibold text-truncate">{{ $clase->titulo }}</div>
        <div class="small text-muted">
            {{ ($clase->fecha_publicacion ?? $clase->created_at)->format('d/m/Y') }}
            @if ($clase->recursos->isNotEmpty())
                · <i class="bi bi-paperclip"></i> {{ $clase->recursos->count() }}
            @endif
        </div>
    </div>
    @if ($puedeEditar)
        @if ($clase->estado === 'oculta')
            <span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>Oculta</span>
        @elseif ($clase->estado === 'programada')
            <span class="badge badge-pendiente"><i class="bi bi-clock me-1"></i>{{ $clase->fecha_publicacion->format('d/m H:i') }}</span>
        @endif
    @endif
    <i class="bi bi-chevron-right text-muted"></i>
</a>
