@extends('layouts.app')

@section('titulo', 'Cargar alumnos — ' . $institucion->nombre)

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('instituciones.show', $institucion) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $institucion->nombre }}</a>
            <h1 class="mt-1"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>Cargar alumnos desde una planilla</h1>
        </div>
    </div>

    @if ($resultado)
        <div class="card border-success mb-4">
            <div class="card-body">
                <h2 class="h6 fw-bold text-success mb-3"><i class="bi bi-check2-circle me-1"></i>Carga terminada</h2>
                <div class="row g-2 text-center mb-3">
                    <div class="col-4"><div class="h4 mb-0">{{ $resultado['creados'] }}</div><div class="small text-muted">Cuentas nuevas</div></div>
                    <div class="col-4"><div class="h4 mb-0">{{ $resultado['vinculados'] }}</div><div class="small text-muted">Ya tenían cuenta</div></div>
                    <div class="col-4"><div class="h4 mb-0">{{ $resultado['inscriptos'] }}</div><div class="small text-muted">Inscriptos{{ $resultado['aula'] ? ' en ' . $resultado['aula'] : '' }}</div></div>
                </div>
                @if ($resultado['creados'])
                    <p class="small mb-2">Las cuentas nuevas entran con <strong>su DNI como usuario y como contraseña</strong>, y el sistema les pide cambiarla la primera vez.</p>
                @endif
                @if ($resultado['errores'])
                    <div class="alert alert-warning small mb-0">
                        <strong>{{ count($resultado['errores']) }} filas con problemas:</strong>
                        <ul class="mb-0 mt-1">
                            @foreach ($resultado['errores'] as $err)<li>{{ $err }}</li>@endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Cómo armar la planilla</h2>
                    <ol class="small mb-3">
                        <li class="mb-1">Descargá la plantilla y abrila con Excel.</li>
                        <li class="mb-1">Completá una fila por alumno: <strong>apellido, nombre, dni</strong> y, si lo tenés, <strong>email</strong>.</li>
                        <li class="mb-1">Guardá con <em>Archivo → Guardar como → CSV (delimitado por comas)</em>.</li>
                        <li>Subí ese archivo acá.</li>
                    </ol>
                    <a href="{{ route('instituciones.importar.plantilla', $institucion) }}" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-download me-1"></i> Descargar plantilla
                    </a>
                    <hr>
                    <ul class="small text-muted mb-0">
                        <li>Si un alumno ya tiene cuenta (mismo DNI o email), no se duplica: solo se lo suma a la institución.</li>
                        <li>Hasta {{ \App\Http\Controllers\ImportarAlumnosController::MAX_FILAS }} alumnos por archivo.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('instituciones.importar.procesar', $institucion) }}" enctype="multipart/form-data"
                          data-enviando="Cargando alumnos...">
                        @csrf
                        <div class="mb-3">
                            <label for="archivo" class="form-label">Archivo CSV</label>
                            <input type="file" class="form-control @error('archivo') is-invalid @enderror" id="archivo" name="archivo" accept=".csv,text/csv" required>
                            @error('archivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label for="aula_id" class="form-label">Inscribirlos además en un aula <span class="text-muted small">(opcional)</span></label>
                            <select class="form-select @error('aula_id') is-invalid @enderror" id="aula_id" name="aula_id">
                                <option value="">No, solo sumarlos a la institución</option>
                                @foreach ($aulas as $a)
                                    <option value="{{ $a->id }}" @selected((string) old('aula_id') === (string) $a->id)>{{ $a->nombre }} — {{ $a->titular?->nombre_completo }}</option>
                                @endforeach
                            </select>
                            @error('aula_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Después los docentes también pueden sumarlos desde su aula con "De la institución".</div>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Cargar alumnos</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
