@extends('layouts.app')

@section('titulo', 'Calificaciones — ' . $aula->nombre)

@push('estilos')
<style>
    .libreta .nota-input { width: 4.2rem; margin: 0 auto; text-align: center; padding: .15rem .25rem; }
    .libreta .col-manual { background-color: #FFFBEB; }
    .libreta .col-cierre { background-color: #ECFDF5; min-width: 100px; }
    .libreta .peso { font-size: .7rem; }
    .libreta-guardar { position: sticky; bottom: 0; z-index: 5; }
</style>
@endpush

@section('contenido')
    @php
        $colorEstado = [
            'nota' => '', 'corregida' => 'text-success', 'corregir' => 'text-primary', 'rehacer' => 'text-warning',
            'curso' => 'text-info', 'falta' => 'text-danger', 'pendiente' => 'text-muted',
        ];
        $iconos = [
            'tarea' => 'bi-clipboard-check text-warning', 'evaluacion' => 'bi-ui-checks text-success', 'manual' => 'bi-pencil-square text-secondary',
        ];
        $tipos = ['tarea' => 'Tarea', 'evaluacion' => 'Evaluación', 'manual' => 'Nota'];
        $num = fn ($v) => $v === null ? '—' : \App\Models\Evaluacion::numero($v);
        $valorInput = fn ($v) => $v === null ? '' : \App\Models\Evaluacion::numero($v);
        $selPeriodo = $periodo ? $periodo->id : 'todo';
        $cargar = $esDocente && $puedeCargar;
        $hayManual = $items->contains('tipo', 'manual');
        $editable = $cargar && ($hayManual || $periodo);
    @endphp

    <div class="page-header">
        <div>
            <a href="{{ route('aulas.show', $aula) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $aula->nombre }}</a>
            <h1 class="mt-1"><i class="bi bi-table text-primary me-2"></i>{{ $esDocente ? 'Calificaciones' : 'Mis calificaciones' }}</h1>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if ($esDocente)
                <form method="GET" class="form-check form-switch mb-0 me-2">
                    <input type="hidden" name="periodo" value="{{ $selPeriodo }}">
                    <input class="form-check-input" type="checkbox" role="switch" id="ceros" name="ceros" value="1"
                           @checked($contarCeros) onchange="this.form.submit()">
                    <label class="form-check-label small" for="ceros">Contar lo no entregado como 0</label>
                </form>
                @if ($puedeCargar)
                    <a href="{{ route('libreta.configurar', $aula) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-sliders me-1"></i> Configurar
                    </a>
                @endif
                <a href="{{ route('libreta.boletin', ['aula' => $aula, 'ceros' => $contarCeros ? 1 : null]) }}" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-1"></i> Boletines
                </a>
                <a href="{{ route('libreta.exportar', ['aula' => $aula, 'periodo' => $selPeriodo, 'ceros' => $contarCeros ? 1 : null]) }}" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </a>
            @elseif ($alumnos->isNotEmpty())
                <a href="{{ route('libreta.boletin', $aula) }}" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-1"></i> Mi boletín
                </a>
            @endif
        </div>
    </div>

    {{-- Pestañas de períodos --}}
    @if ($periodos->isNotEmpty())
        <ul class="nav nav-pills mb-3 flex-wrap gap-1">
            @foreach ($periodos as $p)
                <li class="nav-item">
                    <a class="nav-link {{ $periodo && $periodo->id == $p->id ? 'active' : '' }}"
                       href="{{ route('libreta.index', ['aula' => $aula, 'periodo' => $p->id, 'ceros' => $contarCeros ? 1 : null]) }}">
                        {{ $p->nombre }}
                        <span class="small opacity-75 d-none d-md-inline">· {{ $p->desde->format('d/m') }}–{{ $p->hasta->format('d/m') }}</span>
                    </a>
                </li>
            @endforeach
            <li class="nav-item">
                <a class="nav-link {{ ! $periodo ? 'active' : '' }}" href="{{ route('libreta.index', ['aula' => $aula, 'periodo' => 'todo', 'ceros' => $contarCeros ? 1 : null]) }}">
                    Todo el ciclo
                </a>
            </li>
        </ul>
    @elseif ($esDocente && $puedeCargar)
        <div class="alert alert-light border small d-flex flex-wrap align-items-center gap-2">
            <i class="bi bi-info-circle text-primary"></i>
            <span class="flex-grow-1">¿Tu escuela trabaja por trimestres o cuatrimestres? Configurá los períodos para tener la nota de cierre y el boletín.</span>
            <a href="{{ route('libreta.configurar', $aula) }}" class="btn btn-sm btn-primary">Configurar períodos</a>
        </div>
    @endif

    @if ($items->isEmpty() && ! ($esDocente && $periodo))
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-table display-5"></i>
                <p class="mt-3 mb-0">
                    {{ $periodo ? 'No hay calificaciones en este período.' : 'Todavía no hay tareas, evaluaciones ni notas cargadas en esta aula.' }}
                </p>
                @if ($esDocente && $puedeCargar)
                    <a href="{{ route('libreta.configurar', $aula) }}" class="btn btn-sm btn-outline-primary mt-3">Agregar una columna de nota</a>
                @endif
            </div>
        </div>
    @elseif ($esDocente)
        {{-- ===================== Docente: planilla completa ===================== --}}
        <form method="POST" action="{{ route('libreta.guardar', $aula) }}" id="formLibreta">
            @csrf
            @if ($periodo)
                <input type="hidden" name="periodo_id" value="{{ $periodo->id }}">
            @endif

            <div class="card">
                <div class="table-responsive libreta-contenedor">
                    <table class="table table-bordered table-sm align-middle mb-0 libreta">
                        <thead class="table-light">
                            <tr>
                                <th class="libreta-alumno">Alumno</th>
                                @foreach ($items as $it)
                                    <th class="text-center libreta-col {{ $it['tipo'] === 'manual' ? 'col-manual' : '' }}">
                                        @if ($it['url'])
                                            <a href="{{ $it['url'] }}" class="text-reset text-decoration-none d-block" title="{{ $it['titulo'] }}">
                                        @else
                                            <span class="d-block" title="{{ $it['titulo'] }} (nota cargada a mano)">
                                        @endif
                                            <i class="bi {{ $iconos[$it['tipo']] }}"></i>
                                            <span class="d-block text-truncate small fw-semibold">{{ $it['titulo'] }}</span>
                                        {!! $it['url'] ? '</a>' : '</span>' !!}
                                        <span class="small text-muted fw-normal">/{{ $num($it['max']) }} · {{ $it['fecha']->format('d/m') }}</span>
                                        @if ($it['peso'] != 1)
                                            <span class="badge {{ $it['peso'] == 0 ? 'bg-secondary' : 'bg-primary-subtle text-primary-emphasis' }} peso"
                                                  title="{{ $it['peso'] == 0 ? 'No cuenta para el promedio' : 'Peso en el promedio' }}">
                                                {{ $it['peso'] == 0 ? 'no cuenta' : '×' . $num($it['peso']) }}
                                            </span>
                                        @endif
                                    </th>
                                @endforeach
                                <th class="text-center libreta-promedio">Promedio<br><span class="small text-muted fw-normal">/10</span></th>
                                @if ($periodo)
                                    <th class="text-center col-cierre">Cierre<br><span class="small text-muted fw-normal">{{ $periodo->nombre }}</span></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($alumnos as $al)
                                @php($prom = $promedios[$al->id])
                                @php($cierre = $periodo ? ($cierres[$al->id] ?? null) : null)
                                <tr>
                                    <td class="libreta-alumno">
                                        {{ $al->nombre_completo }}
                                        @if ($prom['faltan'])
                                            <span class="badge bg-danger-subtle text-danger-emphasis ms-1" title="Sin entregar / sin rendir">{{ $prom['faltan'] }}</span>
                                        @endif
                                        <a href="{{ route('libreta.boletin', ['aula' => $aula, 'alumno' => $al->id, 'ceros' => $contarCeros ? 1 : null]) }}" target="_blank"
                                           class="text-muted ms-1" title="Boletín de {{ $al->name }}"><i class="bi bi-printer small"></i></a>
                                    </td>
                                    @foreach ($items as $it)
                                        @php($c = $celdas[$al->id][$it['clave']])
                                        @if ($it['tipo'] === 'manual')
                                            <td class="text-center col-manual" @if ($c['observacion']) title="{{ $c['observacion'] }}" @endif>
                                                @if ($cargar)
                                                    <input type="text" inputmode="decimal" autocomplete="off"
                                                           class="form-control form-control-sm nota-input"
                                                           name="notas[{{ $it['id'] }}][{{ $al->id }}]" value="{{ old("notas.{$it['id']}.{$al->id}", $valorInput($c['nota'])) }}"
                                                           aria-label="{{ $it['titulo'] }} — {{ $al->nombre_completo }}">
                                                @else
                                                    <span class="fw-semibold">{{ $c['texto'] }}</span>
                                                @endif
                                                @if ($cargar)
                                                    <button type="button" class="btn btn-link btn-sm p-0 {{ $c['observacion'] ? 'text-primary' : 'text-muted opacity-50' }}"
                                                            data-bs-toggle="modal" data-bs-target="#modalObs"
                                                            data-tipo="columna" data-id="{{ $it['id'] }}" data-alumno="{{ $al->id }}"
                                                            data-nombre="{{ $al->nombre_completo }} · {{ $it['titulo'] }}" data-obs="{{ $c['observacion'] }}"
                                                            title="Observación"><i class="bi bi-chat-left-text small"></i></button>
                                                @elseif ($c['observacion'])
                                                    <i class="bi bi-chat-left-text text-muted small"></i>
                                                @endif
                                            </td>
                                        @else
                                            <td class="text-center {{ $colorEstado[$c['estado']] ?? '' }} {{ $c['estado'] === 'nota' ? 'fw-semibold' : 'small' }}">
                                                @if ($c['url'])
                                                    <a href="{{ $c['url'] }}" class="text-reset text-decoration-none">{{ $c['texto'] }}</a>
                                                @else
                                                    {{ $c['texto'] }}
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                    <td class="text-center fw-bold libreta-promedio {{ $prom['valor'] !== null && $prom['valor'] < 6 ? 'text-danger' : '' }}">
                                        {{ $num($prom['valor']) }}
                                    </td>
                                    @if ($periodo)
                                        <td class="text-center col-cierre" @if ($cierre?->observacion) title="{{ $cierre->observacion }}" @endif>
                                            @if ($cargar)
                                                <input type="text" inputmode="decimal" autocomplete="off"
                                                       class="form-control form-control-sm nota-input fw-bold"
                                                       name="cierres[{{ $al->id }}]" value="{{ old("cierres.{$al->id}", $valorInput($cierre?->nota)) }}"
                                                       placeholder="{{ $prom['valor'] !== null ? $num(round($prom['valor'])) : '' }}"
                                                       aria-label="Nota de cierre — {{ $al->nombre_completo }}">
                                            @else
                                                <span class="fw-bold">{{ $num($cierre?->nota) }}</span>
                                            @endif
                                            @if ($cargar)
                                                <button type="button" class="btn btn-link btn-sm p-0 {{ $cierre?->observacion ? 'text-primary' : 'text-muted' }}"
                                                        data-bs-toggle="modal" data-bs-target="#modalObs"
                                                        data-tipo="cierre" data-id="{{ $periodo->id }}" data-alumno="{{ $al->id }}"
                                                        data-nombre="{{ $al->nombre_completo }}" data-obs="{{ $cierre?->observacion }}"
                                                        title="Observación para el boletín"><i class="bi bi-chat-left-text small"></i></button>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ $items->count() + ($periodo ? 3 : 2) }}" class="text-center text-muted py-4">El aula todavía no tiene alumnos.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($alumnos->isNotEmpty() && $items->isNotEmpty())
                            <tfoot class="table-light">
                                <tr>
                                    <th class="libreta-alumno">Promedio del curso</th>
                                    @foreach ($items as $it)
                                        <th class="text-center {{ $it['tipo'] === 'manual' ? 'col-manual' : '' }}">
                                            {{ $num($resumenItems[$it['clave']]['promedio']) }}
                                            @if ($resumenItems[$it['clave']]['corregir'])
                                                <div class="small text-primary fw-normal">{{ $resumenItems[$it['clave']]['corregir'] }} por corregir</div>
                                            @endif
                                        </th>
                                    @endforeach
                                    <th class="text-center libreta-promedio">
                                        {{ $num(collect($promedios)->pluck('valor')->filter(fn ($v) => $v !== null)->avg()) }}
                                    </th>
                                    @if ($periodo)
                                        <th class="text-center col-cierre">
                                            {{ $num($cierres->pluck('nota')->filter(fn ($v) => $v !== null)->avg()) }}
                                        </th>
                                    @endif
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                @if ($editable && $alumnos->isNotEmpty())
                    <div class="card-footer bg-white d-flex flex-wrap align-items-center gap-2 libreta-guardar">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar notas</button>
                        <span class="small text-muted">Podés usar coma decimal (7,5). Dejá vacío para borrar.
                            @if ($periodo) Si no cargás el cierre, el boletín usa el promedio. @endif
                        </span>
                    </div>
                @endif
            </div>
        </form>

        <p class="small text-muted mt-3">
            El promedio lleva cada nota a escala 10 y respeta el peso de cada columna (×2 vale doble).
            Las tareas cuentan la nota de la corrección; las evaluaciones, el mejor intento.
            Las columnas <span class="px-1" style="background:#FFFBEB">amarillas</span> son notas cargadas a mano.
        </p>

        @if ($editable)
            {{-- Observación (nota manual o cierre) --}}
            <div class="modal fade" id="modalObs" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('libreta.observacion', $aula) }}" class="modal-content">
                        @csrf
                        <input type="hidden" name="tipo">
                        <input type="hidden" name="id">
                        <input type="hidden" name="alumno_id">
                        <div class="modal-header">
                            <h5 class="modal-title">Observación — <span data-nombre></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <textarea name="observacion" class="form-control" rows="4" maxlength="500"
                                      placeholder="Ej.: Muy buen desempeño, participa en clase."></textarea>
                            <div class="form-text">El alumno la ve en su libreta; la del cierre aparece en el boletín. Guardá antes las notas que hayas cambiado.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar observación</button>
                        </div>
                    </form>
                </div>
            </div>

            @push('scripts')
            <script>
                document.getElementById('modalObs')?.addEventListener('show.bs.modal', (ev) => {
                    const b = ev.relatedTarget, m = ev.currentTarget;
                    m.querySelector('[name=tipo]').value = b.dataset.tipo;
                    m.querySelector('[name=id]').value = b.dataset.id;
                    m.querySelector('[name=alumno_id]').value = b.dataset.alumno;
                    m.querySelector('[name=observacion]').value = b.dataset.obs || '';
                    m.querySelector('[data-nombre]').textContent = b.dataset.nombre;
                });
            </script>
            @endpush
        @endif

        @if ($editable)
            @push('scripts')
            <script>
                // Avisar si se va de la página con notas sin guardar; Enter pasa al alumno de abajo
                (() => {
                    const form = document.getElementById('formLibreta');
                    if (!form) return;
                    let cambios = false;
                    form.addEventListener('input', () => cambios = true);
                    form.addEventListener('submit', () => cambios = false);
                    window.addEventListener('beforeunload', (e) => { if (cambios) { e.preventDefault(); e.returnValue = ''; } });
                    form.addEventListener('keydown', (e) => {
                        if (e.key !== 'Enter' || !e.target.classList.contains('nota-input')) return;
                        e.preventDefault();
                        const td = e.target.closest('td'), fila = td.parentElement.nextElementSibling;
                        const sig = fila?.children[td.cellIndex]?.querySelector('.nota-input');
                        sig ? (sig.focus(), sig.select()) : form.requestSubmit();
                    });
                })();
            </script>
            @endpush
        @endif
    @else
        {{-- ===================== Alumno: su propia libreta ===================== --}}
        @php($yo = $alumnos->first())
        @if ($yo)
            @php($prom = $promedios[$yo->id])
            @php($cierre = $periodo ? ($cierres[$yo->id] ?? null) : null)
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="list-group list-group-flush">
                            @forelse ($items as $it)
                                @php($c = $celdas[$yo->id][$it['clave']])
                                @if ($it['url'])
                                    <a href="{{ $it['url'] }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                                @else
                                    <div class="list-group-item d-flex align-items-center gap-3 py-3">
                                @endif
                                    <i class="bi fs-4 {{ $iconos[$it['tipo']] }}"></i>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $it['titulo'] }}</div>
                                        <div class="small text-muted">
                                            {{ $tipos[$it['tipo']] }} · {{ $it['fecha']->format('d/m/Y') }}
                                            @if ($it['peso'] != 1) · {{ $it['peso'] == 0 ? 'no cuenta para el promedio' : 'vale ×' . $num($it['peso']) }} @endif
                                        </div>
                                        @if ($c['observacion'])
                                            <div class="small mt-1"><i class="bi bi-chat-left-text me-1 text-muted"></i>{{ $c['observacion'] }}</div>
                                        @endif
                                    </div>
                                    @if ($c['estado'] === 'nota')
                                        <span class="fs-5 fw-bold text-nowrap">{{ $c['texto'] }}<span class="small text-muted fw-normal">/{{ $num($it['max']) }}</span></span>
                                    @else
                                        <span class="small text-nowrap {{ $colorEstado[$c['estado']] ?? '' }}">{{ $c['texto'] === '—' ? 'Pendiente' : $c['texto'] }}</span>
                                    @endif
                                {!! $it['url'] ? '</a>' : '</div>' !!}
                            @empty
                                <div class="list-group-item text-center text-muted py-4">No hay calificaciones en este período.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card text-center">
                        <div class="card-body py-4">
                            @if ($cierre && $cierre->nota !== null)
                                <div class="small text-muted mb-1">Nota de {{ $periodo->nombre }}</div>
                                <div class="display-5 fw-bold {{ $cierre->nota < 6 ? 'text-danger' : 'text-success' }}">{{ $num($cierre->nota) }}</div>
                                <div class="small text-muted">Promedio de las actividades: {{ $num($prom['valor']) }}</div>
                            @else
                                <div class="small text-muted mb-1">{{ $periodo ? 'Mi promedio en ' . $periodo->nombre : 'Mi promedio' }}</div>
                                <div class="display-5 fw-bold {{ $prom['valor'] !== null && $prom['valor'] < 6 ? 'text-danger' : 'text-success' }}">{{ $num($prom['valor']) }}</div>
                                <div class="small text-muted">sobre 10 · {{ $prom['calificadas'] }} {{ $prom['calificadas'] === 1 ? 'nota' : 'notas' }}</div>
                            @endif
                            @if ($prom['faltan'])
                                <div class="small text-danger mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $prom['faltan'] }} sin entregar o sin rendir</div>
                            @endif
                            @if ($cierre?->observacion)
                                <div class="small text-start border-top mt-3 pt-3"><i class="bi bi-chat-left-text me-1 text-muted"></i>{{ $cierre->observacion }}</div>
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
