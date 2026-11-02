@extends('layouts.app')

@section('titulo', 'Configurar calificaciones — ' . $aula->nombre)

@section('contenido')
    @php($num = fn ($v) => \App\Models\Evaluacion::numero($v))
    @php($nombresPlantilla = ['trimestres' => '3 trimestres', 'cuatrimestres' => '2 cuatrimestres', 'bimestres' => '4 bimestres', 'anual' => 'Anual'])

    <div class="page-header">
        <div>
            <a href="{{ route('libreta.index', $aula) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Calificaciones</a>
            <h1 class="mt-1"><i class="bi bi-sliders text-primary me-2"></i>Configurar calificaciones</h1>
            <p class="text-muted mb-0">{{ $aula->nombre }}</p>
        </div>
    </div>

    <div class="row g-4">
        {{-- ===================== Períodos ===================== --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h2 class="h5 mb-0"><i class="bi bi-calendar-range me-2 text-primary"></i>Períodos</h2></div>
                <div class="card-body">
                    <p class="small text-muted">
                        Cada tarea y evaluación cae en el período de su fecha límite (o de cierre). Cada período tiene su promedio y su nota de cierre.
                    </p>

                    @forelse ($periodos as $p)
                        <form method="POST" action="{{ route('libreta.periodos.update', [$aula, $p]) }}" class="row g-2 align-items-end mb-2">
                            @csrf @method('PUT')
                            <div class="col-12 col-sm-4">
                                <label class="form-label small mb-0">Nombre</label>
                                <input type="text" name="nombre" value="{{ $p->nombre }}" class="form-control form-control-sm" maxlength="60" required>
                            </div>
                            <div class="col-6 col-sm-3">
                                <label class="form-label small mb-0">Desde</label>
                                <input type="date" name="desde" value="{{ $p->desde->format('Y-m-d') }}" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6 col-sm-3">
                                <label class="form-label small mb-0">Hasta</label>
                                <input type="date" name="hasta" value="{{ $p->hasta->format('Y-m-d') }}" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12 col-sm-2 d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary flex-fill" title="Guardar"><i class="bi bi-check-lg"></i></button>
                                <button type="submit" form="borrarPeriodo{{ $p->id }}" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('libreta.periodos.destroy', [$aula, $p]) }}" id="borrarPeriodo{{ $p->id }}" class="d-none"
                              data-confirmar="¿Eliminar «{{ $p->nombre }}»? Se borran las notas de cierre de ese período.">
                            @csrf @method('DELETE')
                        </form>
                    @empty
                        <div class="alert alert-light border small">Todavía no hay períodos. Elegí una plantilla para empezar:</div>
                    @endforelse

                    <div class="border-top pt-3 mt-3">
                        <div class="small fw-semibold mb-2">{{ $periodos->isEmpty() ? 'Crear con una plantilla' : 'Reemplazar por una plantilla' }} (ciclo {{ $anio }})</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($nombresPlantilla as $clave => $texto)
                                <form method="POST" action="{{ route('libreta.plantilla', $aula) }}"
                                      @if ($periodos->isNotEmpty()) data-confirmar="Se reemplazan los períodos actuales y se borran sus notas de cierre. ¿Seguir?" @endif>
                                    @csrf
                                    <input type="hidden" name="plantilla" value="{{ $clave }}">
                                    <button class="btn btn-sm btn-outline-secondary">{{ $texto }}</button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    <details class="mt-3">
                        <summary class="small fw-semibold">Agregar un período a mano</summary>
                        <form method="POST" action="{{ route('libreta.periodos.store', $aula) }}" class="row g-2 align-items-end mt-1">
                            @csrf
                            <div class="col-12 col-sm-4">
                                <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej.: Recuperatorio" maxlength="60" required>
                            </div>
                            <div class="col-6 col-sm-3"><input type="date" name="desde" class="form-control form-control-sm" required></div>
                            <div class="col-6 col-sm-3"><input type="date" name="hasta" class="form-control form-control-sm" required></div>
                            <div class="col-12 col-sm-2"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg"></i></button></div>
                        </form>
                    </details>
                </div>
            </div>
        </div>

        {{-- ===================== Columnas manuales ===================== --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h2 class="h5 mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i>Notas cargadas a mano</h2></div>
                <div class="card-body">
                    <p class="small text-muted">
                        Para lo que no pasa por el aula: concepto, lección oral, trabajo en clase, carpeta… Las notas se cargan directamente en la libreta.
                    </p>

                    @foreach ($columnas as $c)
                        <form method="POST" action="{{ route('libreta.columnas.update', [$aula, $c]) }}" class="row g-2 align-items-end border rounded p-2 mb-2">
                            @csrf @method('PUT')
                            <div class="col-12 col-sm-6">
                                <label class="form-label small mb-0">Nombre</label>
                                <input type="text" name="nombre" value="{{ $c->nombre }}" class="form-control form-control-sm" maxlength="80" required>
                            </div>
                            <div class="col-6 col-sm-3">
                                <label class="form-label small mb-0">Sobre</label>
                                <input type="number" name="puntaje_maximo" value="{{ $num($c->puntaje_maximo) }}" min="1" max="1000" step="0.5" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6 col-sm-3">
                                <label class="form-label small mb-0">Peso</label>
                                <input type="number" name="peso" value="{{ $num($c->peso) }}" min="0" max="10" step="0.5" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6 col-sm-5">
                                <label class="form-label small mb-0">Fecha</label>
                                <input type="date" name="fecha" value="{{ $c->fecha?->format('Y-m-d') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-sm-4">
                                <label class="form-label small mb-0">Período</label>
                                <select name="periodo_id" class="form-select form-select-sm">
                                    <option value="">Según la fecha</option>
                                    @foreach ($periodos as $p)
                                        <option value="{{ $p->id }}" @selected($c->periodo_id == $p->id)>{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-3 d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary flex-fill" title="Guardar"><i class="bi bi-check-lg"></i></button>
                                <button type="submit" form="borrarColumna{{ $c->id }}" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('libreta.columnas.destroy', [$aula, $c]) }}" id="borrarColumna{{ $c->id }}" class="d-none"
                              data-confirmar="¿Eliminar la columna «{{ $c->nombre }}» y todas sus notas?">
                            @csrf @method('DELETE')
                        </form>
                    @endforeach

                    <form method="POST" action="{{ route('libreta.columnas.store', $aula) }}" class="row g-2 align-items-end bg-light rounded p-2 mt-3">
                        @csrf
                        <div class="col-12 small fw-semibold">Nueva columna</div>
                        <div class="col-12 col-sm-6">
                            <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-control form-control-sm" placeholder="Ej.: Concepto" maxlength="80" required>
                        </div>
                        <div class="col-6 col-sm-3">
                            <input type="number" name="puntaje_maximo" value="{{ old('puntaje_maximo', 10) }}" min="1" max="1000" step="0.5" class="form-control form-control-sm" title="Puntaje máximo" required>
                        </div>
                        <div class="col-6 col-sm-3">
                            <input type="number" name="peso" value="{{ old('peso', 1) }}" min="0" max="10" step="0.5" class="form-control form-control-sm" title="Peso" required>
                        </div>
                        <div class="col-6 col-sm-5">
                            <input type="date" name="fecha" value="{{ old('fecha', now('America/Argentina/Buenos_Aires')->format('Y-m-d')) }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-6 col-sm-4">
                            <select name="periodo_id" class="form-select form-select-sm">
                                <option value="">Según la fecha</option>
                                @foreach ($periodos as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-3"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Agregar</button></div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===================== Pesos ===================== --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white"><h2 class="h5 mb-0"><i class="bi bi-speedometer2 me-2 text-success"></i>Peso de tareas y evaluaciones</h2></div>
                <div class="card-body">
                    <p class="small text-muted">
                        1 = normal · 2 = vale el doble · 0,5 = vale la mitad · 0 = no cuenta para el promedio (por ejemplo, una tarea de práctica).
                    </p>

                    @if ($tareas->isEmpty() && $evaluaciones->isEmpty())
                        <p class="text-muted mb-0">Todavía no hay tareas ni evaluaciones en el aula.</p>
                    @else
                        <form method="POST" action="{{ route('libreta.pesos', $aula) }}">
                            @csrf @method('PUT')
                            <div class="row g-2">
                                @foreach ([['tareas', $tareas, 'bi-clipboard-check text-warning'], ['evaluaciones', $evaluaciones, 'bi-ui-checks text-success']] as [$campo, $lista, $icono])
                                    @foreach ($lista as $m)
                                        <div class="col-md-6 col-xl-4">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text flex-grow-1 text-truncate justify-content-start" title="{{ $m->titulo }}" style="max-width: 80%">
                                                    <i class="bi {{ $icono }} me-2"></i><span class="text-truncate">{{ $m->titulo }}</span>
                                                </span>
                                                <input type="number" name="{{ $campo }}[{{ $m->id }}]" value="{{ $num($m->peso ?? 1) }}"
                                                       min="0" max="10" step="0.5" class="form-control text-center" aria-label="Peso de {{ $m->titulo }}">
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                            <button class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i> Guardar pesos</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
