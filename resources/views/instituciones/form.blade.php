@extends('layouts.app')

@section('titulo', $institucion->exists ? 'Editar institución' : 'Crear institución')

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ $institucion->exists ? route('instituciones.show', $institucion) : route('instituciones.index') }}" class="small text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>{{ $institucion->exists ? $institucion->nombre : 'Instituciones' }}
            </a>
            <h1 class="mt-1"><i class="bi bi-building text-primary me-2"></i>{{ $institucion->exists ? 'Datos de la institución' : 'Crear institución' }}</h1>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    @unless ($institucion->exists)
                        <div class="alert alert-light border small">
                            Vas a quedar como <strong>administrador</strong>. Después invitás a los docentes con un código
                            y podés cargar a los alumnos desde una planilla.
                        </div>
                    @endunless

                    <form method="POST" action="{{ $institucion->exists ? route('instituciones.update', $institucion) : route('instituciones.store') }}">
                        @csrf
                        @if ($institucion->exists) @method('PUT') @endif

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre"
                                   value="{{ old('nombre', $institucion->nombre) }}" required maxlength="150" autofocus>
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <label for="cue" class="form-label">CUE</label>
                                <input type="text" class="form-control @error('cue') is-invalid @enderror" id="cue" name="cue"
                                       value="{{ old('cue', $institucion->cue) }}" maxlength="20">
                                @error('cue')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-4">
                                <label for="nivel" class="form-label">Nivel</label>
                                <select class="form-select @error('nivel') is-invalid @enderror" id="nivel" name="nivel">
                                    <option value="">—</option>
                                    @foreach (\App\Models\Institucion::NIVELES as $valor => $texto)
                                        <option value="{{ $valor }}" @selected(old('nivel', $institucion->nivel) === $valor)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                                @error('nivel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-4">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono"
                                       value="{{ old('telefono', $institucion->telefono) }}" maxlength="40">
                                @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="localidad" class="form-label">Localidad</label>
                                <input type="text" class="form-control @error('localidad') is-invalid @enderror" id="localidad" name="localidad"
                                       value="{{ old('localidad', $institucion->localidad) }}" maxlength="100">
                                @error('localidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="provincia" class="form-label">Provincia</label>
                                <input type="text" class="form-control @error('provincia') is-invalid @enderror" id="provincia" name="provincia"
                                       value="{{ old('provincia', $institucion->provincia) }}" maxlength="60">
                                @error('provincia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label">Email de la institución</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                                   value="{{ old('email', $institucion->email) }}" maxlength="150">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ $institucion->exists ? route('instituciones.show', $institucion) : route('instituciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> {{ $institucion->exists ? 'Guardar cambios' : 'Crear institución' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
