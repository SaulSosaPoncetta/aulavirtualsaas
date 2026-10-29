{{-- Fila de una evaluación en el listado del aula --}}
<a href="{{ route('evaluaciones.show', $evaluacion) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
    <i class="bi bi-ui-checks fs-4 text-success"></i>
    <div class="flex-grow-1 min-w-0">
        <div class="fw-semibold text-truncate">{{ $evaluacion->titulo }}</div>
        <div class="small {{ $evaluacion->estaCerrada() ? 'text-danger' : 'text-muted' }}">
            Evaluación ·
            @if ($evaluacion->fecha_cierre)
                {{ $evaluacion->estaCerrada() ? 'Cerró' : 'Cierra' }} el {{ $evaluacion->fecha_cierre->format('d/m/Y H:i') }}
            @else
                Sin fecha de cierre
            @endif
            @if ($evaluacion->duracion_minutos) · {{ $evaluacion->duracion_minutos }} min @endif
        </div>
    </div>

    @if ($puedeEditar)
        @if ($evaluacion->preguntas_count === 0)
            <span class="badge bg-danger-subtle text-danger-emphasis">Sin preguntas</span>
        @endif
        @if ($evaluacion->estado === 'oculta')
            <span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>Oculta</span>
        @elseif ($evaluacion->estado === 'programada')
            <span class="badge badge-pendiente"><i class="bi bi-clock me-1"></i>{{ $evaluacion->fecha_publicacion->format('d/m H:i') }}</span>
        @endif
        <span class="badge bg-light text-dark" title="Alumnos que rindieron">
            <i class="bi bi-person-check me-1"></i>{{ $rindieron[$evaluacion->id] ?? 0 }}/{{ $alumnos->count() }}
        </span>
        @if ($evaluacion->por_revisar_count)
            <span class="badge bg-warning text-dark">{{ $evaluacion->por_revisar_count }} por corregir</span>
        @endif
    @else
        @php
            $lista = $misIntentos[$evaluacion->id] ?? collect();
            $mejor = $lista->whereNotNull('nota')->sortByDesc(fn ($i) => (float) $i->nota)->first();
        @endphp
        @if ($mejor)
            <span class="badge bg-success">{{ $mejor->nota_texto }}/{{ $evaluacion->puntaje_maximo_texto }}</span>
        @elseif ($lista->contains('estado', 'pendiente_revision'))
            <span class="badge bg-warning text-dark">Falta corregir</span>
        @elseif ($lista->contains('estado', 'en_curso'))
            <span class="badge bg-info">En curso</span>
        @elseif ($lista->isNotEmpty())
            <span class="badge bg-primary">Entregada</span>
        @elseif ($evaluacion->estaCerrada())
            <span class="badge bg-danger">No rendida</span>
        @else
            <span class="badge badge-pendiente">Pendiente</span>
        @endif
    @endif
    <i class="bi bi-chevron-right text-muted"></i>
</a>
