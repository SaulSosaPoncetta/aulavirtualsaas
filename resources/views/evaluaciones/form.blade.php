@extends('layouts.app')

@section('titulo', $evaluacion->exists ? 'Editar evaluación' : 'Nueva evaluación')

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ $evaluacion->exists ? route('evaluaciones.show', $evaluacion) : route('aulas.show', $aula) }}" class="small text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ $evaluacion->exists ? $evaluacion->titulo : $aula->nombre }}
            </a>
            <h1 class="mt-1"><i class="bi bi-ui-checks text-success me-2"></i>{{ $evaluacion->exists ? 'Configuración de la evaluación' : 'Nueva evaluación' }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $evaluacion->exists ? route('evaluaciones.update', $evaluacion) : route('evaluaciones.store', $aula) }}">
        @csrf
        @if ($evaluacion->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('titulo') is-invalid @enderror" id="titulo" name="titulo"
                                   value="{{ old('titulo', $evaluacion->titulo) }}" required maxlength="200" autofocus
                                   placeholder="Ej: Evaluación Unidad 1">
                            @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="unidad_id" class="form-label">Unidad</label>
                            <select class="form-select @error('unidad_id') is-invalid @enderror" id="unidad_id" name="unidad_id">
                                <option value="">General (sin unidad)</option>
                                @foreach ($unidades as $u)
                                    <option value="{{ $u->id }}" @selected((string) old('unidad_id', $evaluacion->unidad_id) === (string) $u->id)>{{ $u->titulo }}</option>
                                @endforeach
                            </select>
                            @error('unidad_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="instrucciones" class="form-label">Instrucciones para el alumno</label>
                            <textarea class="form-control @error('instrucciones') is-invalid @enderror" id="instrucciones" name="instrucciones"
                                      rows="6" maxlength="10000"
                                      placeholder="Qué temas entran, si pueden usar calculadora, etc.">{{ old('instrucciones', $evaluacion->instrucciones) }}</textarea>
                            @error('instrucciones')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="fecha_publicacion" class="form-label">Abre el</label>
                                <input type="datetime-local" class="form-control @error('fecha_publicacion') is-invalid @enderror"
                                       id="fecha_publicacion" name="fecha_publicacion"
                                       value="{{ old('fecha_publicacion', $evaluacion->fecha_publicacion?->format('Y-m-d\TH:i')) }}">
                                @error('fecha_publicacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Vacío = ya.</div>
                            </div>
                            <div class="col-sm-6">
                                <label for="fecha_cierre" class="form-label">Cierra el</label>
                                <input type="datetime-local" class="form-control @error('fecha_cierre') is-invalid @enderror"
                                       id="fecha_cierre" name="fecha_cierre"
                                       value="{{ old('fecha_cierre', $evaluacion->fecha_cierre?->format('Y-m-d\TH:i')) }}">
                                @error('fecha_cierre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Vacío = sin cierre.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="duracion_minutos" class="form-label">Duración (min)</label>
                                <input type="number" class="form-control @error('duracion_minutos') is-invalid @enderror"
                                       id="duracion_minutos" name="duracion_minutos" min="1" max="600"
                                       value="{{ old('duracion_minutos', $evaluacion->duracion_minutos) }}" placeholder="Sin límite">
                                @error('duracion_minutos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="intentos_permitidos" class="form-label">Intentos</label>
                                <input type="number" class="form-control @error('intentos_permitidos') is-invalid @enderror"
                                       id="intentos_permitidos" name="intentos_permitidos" min="1" max="10" required
                                       value="{{ old('intentos_permitidos', $evaluacion->intentos_permitidos) }}">
                                @error('intentos_permitidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="puntaje_maximo" class="form-label">Nota máxima</label>
                            <input type="number" class="form-control @error('puntaje_maximo') is-invalid @enderror"
                                   id="puntaje_maximo" name="puntaje_maximo" step="0.5" min="1" max="100" required
                                   value="{{ old('puntaje_maximo', (float) $evaluacion->puntaje_maximo) }}">
                            @error('puntaje_maximo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Los puntos de las preguntas se llevan a esta escala.</div>
                        </div>

                        <div class="mb-3">
                            <label for="mostrar_resultados" class="form-label">El alumno ve las respuestas correctas</label>
                            <select class="form-select" id="mostrar_resultados" name="mostrar_resultados">
                                @foreach (\App\Models\Evaluacion::RESULTADOS as $valor => $texto)
                                    <option value="{{ $valor }}" @selected(old('mostrar_resultados', $evaluacion->mostrar_resultados) === $valor)>{{ $texto }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="mezclar_preguntas" name="mezclar_preguntas" value="1"
                                   @checked(old('mezclar_preguntas', $evaluacion->mezclar_preguntas))>
                            <label class="form-check-label" for="mezclar_preguntas">Mezclar preguntas y opciones</label>
                        </div>
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="visible" name="visible" value="1"
                                   @checked(old('visible', $evaluacion->exists ? $evaluacion->visible : true))>
                            <label class="form-check-label" for="visible">Visible para los alumnos</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i> {{ $evaluacion->exists ? 'Guardar cambios' : 'Crear y cargar preguntas' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
