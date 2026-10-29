@extends('layouts.app')

@section('titulo', $tarea->exists ? 'Editar tarea' : 'Nueva tarea')

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('aulas.show', $aula) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $aula->nombre }}</a>
            <h1 class="mt-1"><i class="bi bi-clipboard-plus text-primary me-2"></i>{{ $tarea->exists ? 'Editar tarea' : 'Nueva tarea' }}</h1>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $tarea->exists ? route('tareas.update', $tarea) : route('tareas.store', $aula) }}"
          data-enviando="Guardando...">
        @csrf
        @if ($tarea->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('titulo') is-invalid @enderror" id="titulo" name="titulo"
                                   value="{{ old('titulo', $tarea->titulo) }}" required maxlength="200" autofocus
                                   placeholder="Ej: Trabajo práctico N° 2 — Ecuaciones">
                            @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="consigna" class="form-label">Consigna</label>
                            <textarea class="form-control @error('consigna') is-invalid @enderror" id="consigna" name="consigna"
                                      rows="12" maxlength="60000"
                                      placeholder="Qué tienen que hacer, cómo entregarlo, criterios de evaluación...">{{ old('consigna', $tarea->consigna) }}</textarea>
                            @error('consigna')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-paperclip me-1"></i>Archivos de la consigna</h2>

                        @if ($tarea->exists && $tarea->archivos->isNotEmpty())
                            <ul class="list-group mb-3">
                                @foreach ($tarea->archivos as $a)
                                    <li class="list-group-item d-flex align-items-center gap-2">
                                        <i class="bi {{ $a->icono }} fs-5"></i>
                                        <span class="flex-grow-1 text-truncate">{{ $a->nombre }}</span>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" name="quitar_archivos[]" value="{{ $a->id }}" id="quitar{{ $a->id }}">
                                            <label class="form-check-label small text-danger" for="quitar{{ $a->id }}">Quitar</label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <input type="file" class="form-control @error('archivos.*') is-invalid @enderror @error('archivos') is-invalid @enderror"
                               name="archivos[]" multiple>
                        @error('archivos.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @error('archivos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Opcional. Hasta 10 archivos de 20 MB cada uno.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="unidad_id" class="form-label">Unidad</label>
                            <select class="form-select @error('unidad_id') is-invalid @enderror" id="unidad_id" name="unidad_id">
                                <option value="">General (sin unidad)</option>
                                @foreach ($unidades as $u)
                                    <option value="{{ $u->id }}" @selected((string) old('unidad_id', $tarea->unidad_id) === (string) $u->id)>{{ $u->titulo }}</option>
                                @endforeach
                            </select>
                            @error('unidad_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="fecha_limite" class="form-label">Fecha límite de entrega</label>
                            <input type="datetime-local" class="form-control @error('fecha_limite') is-invalid @enderror"
                                   id="fecha_limite" name="fecha_limite"
                                   value="{{ old('fecha_limite', $tarea->fecha_limite?->format('Y-m-d\TH:i')) }}">
                            @error('fecha_limite')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Vacío = sin fecha límite.</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="acepta_tardias" name="acepta_tardias" value="1"
                                   @checked(old('acepta_tardias', $tarea->acepta_tardias))>
                            <label class="form-check-label" for="acepta_tardias">Aceptar entregas fuera de término</label>
                        </div>

                        <div class="mb-3">
                            <label for="puntaje_maximo" class="form-label">Nota máxima</label>
                            <input type="number" class="form-control @error('puntaje_maximo') is-invalid @enderror"
                                   id="puntaje_maximo" name="puntaje_maximo" step="0.5" min="1" max="100"
                                   value="{{ old('puntaje_maximo', (float) $tarea->puntaje_maximo) }}" required>
                            @error('puntaje_maximo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="fecha_publicacion" class="form-label">Publicar el</label>
                            <input type="datetime-local" class="form-control @error('fecha_publicacion') is-invalid @enderror"
                                   id="fecha_publicacion" name="fecha_publicacion"
                                   value="{{ old('fecha_publicacion', $tarea->fecha_publicacion?->format('Y-m-d\TH:i')) }}">
                            @error('fecha_publicacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Vacío = se publica al guardar.</div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="visible" name="visible" value="1"
                                   @checked(old('visible', $tarea->exists ? $tarea->visible : true))>
                            <label class="form-check-label" for="visible">Visible para los alumnos</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i> {{ $tarea->exists ? 'Guardar cambios' : 'Publicar tarea' }}
                        </button>
                        <a href="{{ $tarea->exists ? route('tareas.show', $tarea) : route('aulas.show', $aula) }}"
                           class="btn btn-outline-secondary w-100 mt-2">Cancelar</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
