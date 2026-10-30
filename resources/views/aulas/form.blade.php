@extends('layouts.app')

@section('titulo', $aula->exists ? 'Editar aula' : 'Crear aula')

@section('contenido')
    <div class="page-header">
        <h1>
            <i class="bi {{ $aula->exists ? 'bi-pencil-square' : 'bi-plus-square' }} text-primary me-2"></i>
            {{ $aula->exists ? 'Editar aula' : 'Crear aula' }}
        </h1>
        <a href="{{ $aula->exists ? route('aulas.show', $aula) : route('inicio') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ $aula->exists ? route('aulas.update', $aula) : route('aulas.store') }}">
                        @csrf
                        @if ($aula->exists) @method('PUT') @endif

                        <div class="mb-3">
                            <label for="materia" class="form-label">Materia <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('materia') is-invalid @enderror"
                                   id="materia" name="materia" value="{{ old('materia', $aula->materia) }}"
                                   required maxlength="120" placeholder="Ej: Matemática" autofocus>
                            @error('materia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-3">
                                <label for="curso" class="form-label">Curso / Año</label>
                                <input type="text" class="form-control @error('curso') is-invalid @enderror"
                                       id="curso" name="curso" value="{{ old('curso', $aula->curso) }}" maxlength="30" placeholder="3°">
                                @error('curso')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-3">
                                <label for="division" class="form-label">División</label>
                                <input type="text" class="form-control @error('division') is-invalid @enderror"
                                       id="division" name="division" value="{{ old('division', $aula->division) }}" maxlength="30" placeholder="A">
                                @error('division')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-3">
                                <label for="turno" class="form-label">Turno</label>
                                <select class="form-select @error('turno') is-invalid @enderror" id="turno" name="turno">
                                    <option value="">—</option>
                                    @foreach (['Mañana', 'Tarde', 'Vespertino', 'Noche'] as $t)
                                        <option value="{{ $t }}" @selected(old('turno', $aula->turno) === $t)>{{ $t }}</option>
                                    @endforeach
                                </select>
                                @error('turno')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-3">
                                <label for="ciclo_lectivo" class="form-label">Ciclo lectivo <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('ciclo_lectivo') is-invalid @enderror"
                                       id="ciclo_lectivo" name="ciclo_lectivo" value="{{ old('ciclo_lectivo', $aula->ciclo_lectivo) }}"
                                       required min="2020" max="2100">
                                @error('ciclo_lectivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="escuela" class="form-label">Escuela / Institución</label>
                            <input type="text" class="form-control @error('escuela') is-invalid @enderror"
                                   id="escuela" name="escuela" value="{{ old('escuela', $aula->escuela) }}"
                                   maxlength="150" placeholder="Ej: EES N° 5 — Tandil">
                            @error('escuela')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion"
                                      name="descripcion" rows="3" maxlength="2000"
                                      placeholder="Una presentación breve del aula para tus alumnos">{{ old('descripcion', $aula->descripcion) }}</textarea>
                            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-block">Color</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($colores as $c)
                                    <input type="radio" class="btn-check" name="color" id="color{{ $loop->index }}" value="{{ $c }}"
                                           @checked(old('color', $aula->color) === $c)>
                                    <label class="btn p-0 border-0 selector-color" for="color{{ $loop->index }}"
                                           style="background-color: {{ $c }}" title="{{ $c }}"></label>
                                @endforeach
                            </div>
                            @error('color')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-block">Muro del aula</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="alumnos_comentan" name="alumnos_comentan" value="1"
                                       @checked(old('alumnos_comentan', $aula->alumnos_comentan))>
                                <label class="form-check-label" for="alumnos_comentan">Los alumnos pueden comentar</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="alumnos_publican" name="alumnos_publican" value="1"
                                       @checked(old('alumnos_publican', $aula->alumnos_publican))>
                                <label class="form-check-label" for="alumnos_publican">Los alumnos pueden hacer publicaciones</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ $aula->exists ? route('aulas.show', $aula) : route('inicio') }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> {{ $aula->exists ? 'Guardar cambios' : 'Crear aula' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
