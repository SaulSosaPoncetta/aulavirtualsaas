@extends('layouts.app')

@section('titulo', 'Duplicar aula')

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('aulas.show', $aula) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $aula->nombre }}</a>
            <h1 class="mt-1"><i class="bi bi-copy text-primary me-2"></i>Duplicar aula</h1>
            <div class="small text-muted mt-1">Se crea un aula nueva con el contenido de esta, <strong>sin alumnos, entregas, notas ni muro</strong>.</div>
        </div>
    </div>

    @if (! $personalActivo && $instituciones->isEmpty())
        <div class="alert alert-warning">
            Para crear aulas necesitás tu abono activo o una institución con abono. <a href="{{ route('abonos.mio') }}">Ver mi abono</a>
        </div>
    @endif

    <form method="POST" action="{{ route('aulas.duplicar.store', $aula) }}" data-enviando="Duplicando...">
        @csrf
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-3">Datos de la nueva aula</h2>

                        <div class="mb-3">
                            <label for="materia" class="form-label">Materia <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('materia') is-invalid @enderror" id="materia" name="materia"
                                   value="{{ old('materia', $aula->materia) }}" required maxlength="120">
                            @error('materia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-3">
                                <label for="curso" class="form-label">Curso / Año</label>
                                <input type="text" class="form-control" id="curso" name="curso" value="{{ old('curso', $aula->curso) }}" maxlength="30">
                            </div>
                            <div class="col-sm-3">
                                <label for="division" class="form-label">División</label>
                                <input type="text" class="form-control" id="division" name="division" value="{{ old('division', $aula->division) }}" maxlength="30">
                            </div>
                            <div class="col-sm-3">
                                <label for="turno" class="form-label">Turno</label>
                                <select class="form-select" id="turno" name="turno">
                                    <option value="">—</option>
                                    @foreach (['Mañana', 'Tarde', 'Vespertino', 'Noche'] as $t)
                                        <option value="{{ $t }}" @selected(old('turno', $aula->turno) === $t)>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <label for="ciclo_lectivo" class="form-label">Ciclo <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('ciclo_lectivo') is-invalid @enderror" id="ciclo_lectivo" name="ciclo_lectivo"
                                       value="{{ old('ciclo_lectivo', $cicloSugerido) }}" min="2020" max="2100" required>
                                @error('ciclo_lectivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="institucion_id" class="form-label">¿De quién es el aula?</label>
                            <select class="form-select @error('institucion_id') is-invalid @enderror" id="institucion_id" name="institucion_id">
                                <option value="" @disabled(! $personalActivo)>Personal{{ $personalActivo ? '' : ' — requiere abono' }}</option>
                                @foreach ($instituciones as $inst)
                                    <option value="{{ $inst->id }}" @selected((string) old('institucion_id', $aula->institucion_id) === (string) $inst->id)>{{ $inst->nombre }}</option>
                                @endforeach
                            </select>
                            @error('institucion_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div id="bloqueEscuela">
                            <label for="escuela" class="form-label">Escuela</label>
                            <input type="text" class="form-control" id="escuela" name="escuela" value="{{ old('escuela', $aula->escuela) }}" maxlength="150">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-3">Qué copiar</h2>
                        <p class="small text-muted">Las {{ $resumen['unidades'] }} unidades se copian siempre.</p>

                        @foreach ([
                            ['copiar_clases', 'Clases y su material', $resumen['clases'] . ' clases (con archivos y enlaces)'],
                            ['copiar_tareas', 'Tareas', $resumen['tareas'] . ' tareas (con los archivos de la consigna)'],
                            ['copiar_evaluaciones', 'Evaluaciones', $resumen['evaluaciones'] . ' evaluaciones, ' . $resumen['preguntas'] . ' preguntas'],
                        ] as [$campo, $titulo, $detalle])
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="{{ $campo }}" value="1" id="{{ $campo }}" @checked(old($campo, true))>
                                <label class="form-check-label" for="{{ $campo }}">
                                    {{ $titulo }} <span class="d-block small text-muted">{{ $detalle }}</span>
                                </label>
                            </div>
                        @endforeach

                        <hr>

                        <label class="form-label fw-semibold">Fechas de publicación y vencimiento</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="fechas" value="quitar" id="fechasQuitar" @checked(old('fechas', 'quitar') === 'quitar')>
                            <label class="form-check-label" for="fechasQuitar">Quitarlas <span class="d-block small text-muted">Las cargás de nuevo cuando corresponda.</span></label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="fechas" value="correr" id="fechasCorrer" @checked(old('fechas') === 'correr')>
                            <label class="form-check-label" for="fechasCorrer">Correrlas al nuevo ciclo <span class="d-block small text-muted">Mismo día y hora, sumando los años de diferencia.</span></label>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" name="ocultar" value="1" id="ocultar" @checked(old('ocultar', true))>
                            <label class="form-check-label" for="ocultar">
                                Copiar todo <strong>oculto</strong>
                                <span class="d-block small text-muted">Así lo revisás y lo vas publicando a medida que avanza el año.</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" @disabled(! $personalActivo && $instituciones->isEmpty())>
                            <i class="bi bi-copy me-1"></i> Duplicar aula
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('institucion_id');
    const act = () => document.getElementById('bloqueEscuela').classList.toggle('d-none', sel.value !== '');
    sel.addEventListener('change', act);
    act();
});
</script>
@endpush
