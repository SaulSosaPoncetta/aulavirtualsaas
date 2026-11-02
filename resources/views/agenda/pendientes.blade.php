@extends('layouts.app')

@section('titulo', 'Mis pendientes')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-list-check text-primary me-2"></i>Mis pendientes</h1>
        <a href="{{ route('agenda.calendario') }}" class="btn btn-outline-primary"><i class="bi bi-calendar3 me-1"></i> Ver calendario</a>
    </div>

    @if (! $alumno && ! $docente)
        <div class="card"><div class="card-body text-center text-muted py-5">
            <i class="bi bi-emoji-smile display-5"></i>
            <p class="mt-3 mb-0">Todavía no participás de ningún aula activa.</p>
        </div></div>
    @endif

    {{-- ===================== Docente ===================== --}}
    @if ($docente)
        @if ($alumno)<h2 class="h5 fw-bold mb-3">Como docente</h2>@endif
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3"><i class="bi bi-inbox me-1 text-primary"></i>Entregas por corregir</h3>
                        @forelse ($docente['entregasPorCorregir'] as $g)
                            <a href="{{ route('entregas.show', $g['primera']) }}" class="d-flex align-items-center gap-2 py-2 border-bottom text-decoration-none text-reset">
                                <span class="punto-aula" style="background-color: {{ $g['tarea']->aula->color }}"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $g['tarea']->titulo }}</div>
                                    <div class="small text-muted text-truncate">{{ $g['tarea']->aula->nombre }}</div>
                                </div>
                                <span class="badge bg-primary">{{ $g['cantidad'] }}</span>
                            </a>
                        @empty
                            <p class="small text-muted mb-0"><i class="bi bi-check2-circle me-1 text-success"></i>No tenés entregas sin corregir.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3"><i class="bi bi-pencil-square me-1 text-warning"></i>Evaluaciones con respuestas para corregir</h3>
                        @forelse ($docente['intentosPorCorregir'] as $g)
                            <a href="{{ route('intentos.show', $g['primero']) }}" class="d-flex align-items-center gap-2 py-2 border-bottom text-decoration-none text-reset">
                                <span class="punto-aula" style="background-color: {{ $g['evaluacion']->aula->color }}"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $g['evaluacion']->titulo }}</div>
                                    <div class="small text-muted text-truncate">{{ $g['evaluacion']->aula->nombre }}</div>
                                </div>
                                <span class="badge bg-warning text-dark">{{ $g['cantidad'] }}</span>
                            </a>
                        @empty
                            <p class="small text-muted mb-0"><i class="bi bi-check2-circle me-1 text-success"></i>Nada para corregir.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3"><i class="bi bi-calendar-week me-1"></i>Vencimientos de los próximos 14 días</h3>
                        @forelse ($docente['proximos'] as $e)
                            @include('agenda._evento', ['e' => $e])
                        @empty
                            <p class="small text-muted mb-0">No hay vencimientos próximos.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===================== Alumno ===================== --}}
    @if ($alumno)
        @if ($docente)<h2 class="h5 fw-bold mb-3">Como alumno</h2>@endif
        <div class="row g-4">
            <div class="col-lg-7">
                @if ($alumno['paraRehacer']->isNotEmpty())
                    <div class="card border-warning mb-4">
                        <div class="card-body">
                            <h3 class="h6 fw-bold mb-3 text-warning-emphasis"><i class="bi bi-arrow-return-left me-1"></i>Para rehacer</h3>
                            @foreach ($alumno['paraRehacer'] as $t)
                                <a href="{{ route('tareas.show', $t) }}" class="d-flex align-items-center gap-2 py-2 border-bottom text-decoration-none text-reset">
                                    <span class="punto-aula" style="background-color: {{ $t->aula->color }}"></span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $t->titulo }}</div>
                                        <div class="small text-muted">{{ $t->aula->nombre }}</div>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3"><i class="bi bi-clipboard-check me-1 text-warning"></i>Tareas por entregar</h3>
                        @forelse ($alumno['porEntregar'] as $t)
                            @php($vencida = $t->estaVencida())
                            <a href="{{ route('tareas.show', $t) }}" class="d-flex align-items-center gap-2 py-2 border-bottom text-decoration-none text-reset">
                                <span class="punto-aula" style="background-color: {{ $t->aula->color }}"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $t->titulo }}</div>
                                    <div class="small text-muted text-truncate">{{ $t->aula->nombre }}</div>
                                </div>
                                @if ($t->fecha_limite)
                                    <span class="small text-nowrap {{ $vencida ? 'text-danger fw-semibold' : ($t->fecha_limite->lte(now('America/Argentina/Buenos_Aires')->addDay()) ? 'text-warning-emphasis fw-semibold' : 'text-muted') }}">
                                        {{ $vencida ? 'Atrasada' : $t->fecha_limite->format('d/m H:i') }}
                                    </span>
                                @else
                                    <span class="small text-muted">Sin fecha</span>
                                @endif
                            </a>
                        @empty
                            <p class="small text-muted mb-0"><i class="bi bi-check2-circle me-1 text-success"></i>¡Estás al día con las tareas!</p>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3"><i class="bi bi-ui-checks me-1 text-success"></i>Evaluaciones por rendir</h3>
                        @forelse ($alumno['porRendir'] as $ev)
                            <a href="{{ route('evaluaciones.show', $ev) }}" class="d-flex align-items-center gap-2 py-2 border-bottom text-decoration-none text-reset">
                                <span class="punto-aula" style="background-color: {{ $ev->aula->color }}"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $ev->titulo }}</div>
                                    <div class="small text-muted text-truncate">{{ $ev->aula->nombre }}{{ $ev->duracion_minutos ? ' · ' . $ev->duracion_minutos . ' min' : '' }}</div>
                                </div>
                                <span class="small text-muted text-nowrap">{{ $ev->fecha_cierre ? 'Cierra ' . $ev->fecha_cierre->format('d/m H:i') : 'Sin cierre' }}</span>
                            </a>
                        @empty
                            <p class="small text-muted mb-0"><i class="bi bi-check2-circle me-1 text-success"></i>No tenés evaluaciones pendientes.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3"><i class="bi bi-patch-check me-1 text-success"></i>Últimas notas</h3>
                        @forelse ($alumno['ultimasNotas'] as $n)
                            <a href="{{ $n['url'] }}" class="d-flex align-items-center gap-2 py-2 border-bottom text-decoration-none text-reset">
                                <span class="punto-aula" style="background-color: {{ $n['aula']?->color }}"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $n['titulo'] }}</div>
                                    <div class="small text-muted text-truncate">{{ $n['aula']?->nombre }} · {{ $n['fecha']?->format('d/m') }}</div>
                                </div>
                                <span class="fw-bold">{{ $n['nota'] }}<span class="small text-muted fw-normal">/{{ $n['max'] }}</span></span>
                            </a>
                        @empty
                            <p class="small text-muted mb-0">Todavía no tenés notas.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
