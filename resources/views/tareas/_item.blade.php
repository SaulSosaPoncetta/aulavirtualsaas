{{-- Fila de una tarea en el listado del aula --}}
<a href="{{ route('tareas.show', $tarea) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
    <i class="bi bi-clipboard-check fs-4 text-warning"></i>
    <div class="flex-grow-1 min-w-0">
        <div class="fw-semibold text-truncate">{{ $tarea->titulo }}</div>
        <div class="small {{ $tarea->estaVencida() ? 'text-danger' : 'text-muted' }}">
            Tarea ·
            @if ($tarea->fecha_limite)
                {{ $tarea->estaVencida() ? 'Venció' : 'Vence' }} el {{ $tarea->fecha_limite->format('d/m/Y H:i') }}
            @else
                Sin fecha límite
            @endif
        </div>
    </div>

    @if ($puedeEditar)
        @if ($tarea->estado === 'oculta')
            <span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>Oculta</span>
        @elseif ($tarea->estado === 'programada')
            <span class="badge badge-pendiente"><i class="bi bi-clock me-1"></i>{{ $tarea->fecha_publicacion->format('d/m H:i') }}</span>
        @endif
        <span class="badge bg-light text-dark" title="Entregas recibidas">
            <i class="bi bi-inbox me-1"></i>{{ $tarea->entregas_count }}/{{ $alumnos->count() }}
        </span>
        @if ($tarea->por_corregir_count)
            <span class="badge bg-primary" title="Por corregir">{{ $tarea->por_corregir_count }} por corregir</span>
        @endif
    @else
        @php($mia = $misEntregas[$tarea->id] ?? null)
        @if ($mia && $mia->estado === 'corregida')
            <span class="badge bg-success">
                {{ $mia->nota_texto !== null ? $mia->nota_texto . '/' . $tarea->puntaje_maximo_texto : 'Corregida' }}
            </span>
        @elseif ($mia)
            <span class="badge bg-{{ $mia->estado_color }}">{{ $mia->estado_label }}</span>
        @elseif ($tarea->estaVencida())
            <span class="badge bg-danger">{{ $tarea->acepta_tardias ? 'Atrasada' : 'No entregada' }}</span>
        @else
            <span class="badge badge-pendiente">Pendiente</span>
        @endif
    @endif
    <i class="bi bi-chevron-right text-muted"></i>
</a>
