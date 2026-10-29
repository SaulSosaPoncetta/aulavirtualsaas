@extends('layouts.app')

@section('titulo', 'Calificaciones — ' . $aula->nombre)

@section('contenido')
    @php
        $colorEstado = [
            'nota'      => '',
            'corregida' => 'text-success',
            'corregir'  => 'text-primary',
            'rehacer'   => 'text-warning',
            'curso'     => 'text-info',
            'falta'     => 'text-danger',
            'pendiente' => 'text-muted',
        ];
        $num = fn ($v) => $v === null ? '—' : \App\Models\Evaluacion::numero($v);
    @endphp

    <div class="page-header">
        <div>
            <a href="{{ route('aulas.show', $aula) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $aula->nombre }}</a>
            <h1 class="mt-1"><i class="bi bi-table text-primary me-2"></i>{{ $esDocente ? 'Calificaciones' : 'Mis calificaciones' }}</h1>
        </div>

        @if ($esDocente)
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <form method="GET" class="form-check form-switch mb-0 me-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="ceros" name="ceros" value="1"
                           @checked($contarCeros) onchange="this.form.submit()">
                    <label class="form-check-label small" for="ceros">Contar lo no entregado como 0</label>
                </form>
                <a href="{{ route('libreta.exportar', ['aula' => $aula, 'ceros' => $contarCeros ? 1 : null]) }}" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel me-1"></i> Exportar a Excel
                </a>
            </div>
        @endif
    </div>

    @if ($items->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-table display-5"></i>
                <p class="mt-3 mb-0">Todavía no hay tareas ni evaluaciones publicadas en esta aula.</p>
            </div>
        </div>
    @elseif ($esDocente)
        {{-- ===================== Docente: planilla completa ===================== --}}
        <div class="card">
            <div class="table-responsive libreta-contenedor">
                <table class="table table-bordered table-sm align-middle mb-0 libreta">
                    <thead class="table-light">
                        <tr>
                            <th class="libreta-alumno">Alumno</th>
                            @foreach ($items as $it)
                                <th class="text-center libreta-col">
                                    <a href="{{ $it['url'] }}" class="text-reset text-decoration-none d-block" title="{{ $it['titulo'] }}">
                                        <i class="bi {{ $it['tipo'] === 'tarea' ? 'bi-clipboard-check text-warning' : 'bi-ui-checks text-success' }}"></i>
                                        <span class="d-block text-truncate small fw-semibold">{{ $it['titulo'] }}</span>
                                    </a>
                                    <span class="small text-muted fw-normal">/{{ $num($it['max']) }} · {{ $it['fecha']->format('d/m') }}</span>
                                </th>
                            @endforeach
                            <th class="text-center libreta-promedio">Promedio<br><span class="small text-muted fw-normal">/10</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($alumnos as $al)
                            @php($prom = $promedios[$al->id])
                            <tr>
                                <td class="libreta-alumno">
                                    {{ $al->nombre_completo }}
                                    @if ($prom['faltan'])
                                        <span class="badge bg-danger-subtle text-danger-emphasis ms-1" title="Sin entregar / sin rendir">{{ $prom['faltan'] }}</span>
                                    @endif
                                </td>
                                @foreach ($items as $it)
                                    @php($c = $celdas[$al->id][$it['clave']])
                                    <td class="text-center {{ $colorEstado[$c['estado']] ?? '' }} {{ $c['estado'] === 'nota' ? 'fw-semibold' : 'small' }}">
                                        @if ($c['url'])
                                            <a href="{{ $c['url'] }}" class="text-reset text-decoration-none">{{ $c['texto'] }}</a>
                                        @else
                                            {{ $c['texto'] }}
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center fw-bold libreta-promedio {{ $prom['valor'] !== null && $prom['valor'] < 6 ? 'text-danger' : '' }}">
                                    {{ $num($prom['valor']) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $items->count() + 2 }}" class="text-center text-muted py-4">El aula todavía no tiene alumnos.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($alumnos->isNotEmpty())
                        <tfoot class="table-light">
                            <tr>
                                <th class="libreta-alumno">Promedio del curso</th>
                                @foreach ($items as $it)
                                    <th class="text-center">
                                        {{ $num($resumenItems[$it['clave']]['promedio']) }}
                                        @if ($resumenItems[$it['clave']]['corregir'])
                                            <div class="small text-primary fw-normal">{{ $resumenItems[$it['clave']]['corregir'] }} por corregir</div>
                                        @endif
                                    </th>
                                @endforeach
                                <th class="text-center libreta-promedio">
                                    {{ $num(collect($promedios)->pluck('valor')->filter(fn ($v) => $v !== null)->avg()) }}
                                </th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <p class="small text-muted mt-3">
            El promedio lleva cada nota a escala 10. Las tareas cuentan la nota de la corrección y las evaluaciones, el mejor intento.
            Hacé clic en una nota para ver la entrega o el intento.
        </p>
    @else
        {{-- ===================== Alumno: su propia libreta ===================== --}}
        @php($yo = $alumnos->first())
        @if ($yo)
            @php($prom = $promedios[$yo->id])
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="list-group list-group-flush">
                            @foreach ($items as $it)
                                @php($c = $celdas[$yo->id][$it['clave']])
                                <a href="{{ $it['url'] }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                                    <i class="bi fs-4 {{ $it['tipo'] === 'tarea' ? 'bi-clipboard-check text-warning' : 'bi-ui-checks text-success' }}"></i>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $it['titulo'] }}</div>
                                        <div class="small text-muted">{{ $it['tipo'] === 'tarea' ? 'Tarea' : 'Evaluación' }} · {{ $it['fecha']->format('d/m/Y') }}</div>
                                    </div>
                                    @if ($c['estado'] === 'nota')
                                        <span class="fs-5 fw-bold">{{ $c['texto'] }}<span class="small text-muted fw-normal">/{{ $num($it['max']) }}</span></span>
                                    @else
                                        <span class="small {{ $colorEstado[$c['estado']] ?? '' }}">{{ $c['texto'] === '—' ? 'Pendiente' : $c['texto'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card text-center">
                        <div class="card-body py-4">
                            <div class="small text-muted mb-1">Mi promedio</div>
                            <div class="display-5 fw-bold {{ $prom['valor'] !== null && $prom['valor'] < 6 ? 'text-danger' : 'text-success' }}">{{ $num($prom['valor']) }}</div>
                            <div class="small text-muted">sobre 10 · {{ $prom['calificadas'] }} {{ $prom['calificadas'] === 1 ? 'nota' : 'notas' }}</div>
                            @if ($prom['faltan'])
                                <div class="small text-danger mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $prom['faltan'] }} sin entregar o sin rendir</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info">Las calificaciones son solo para los alumnos del aula.</div>
        @endif
    @endif
@endsection
