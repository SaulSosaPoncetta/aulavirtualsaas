@extends('layouts.app')

@section('titulo', $clase->exists ? 'Editar clase' : 'Nueva clase')

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('aulas.show', $aula) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $aula->nombre }}</a>
            <h1 class="mt-1"><i class="bi bi-journal-plus text-primary me-2"></i>{{ $clase->exists ? 'Editar clase' : 'Nueva clase' }}</h1>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $clase->exists ? route('clases.update', $clase) : route('clases.store', $aula) }}"
          data-enviando="Guardando...">
        @csrf
        @if ($clase->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('titulo') is-invalid @enderror" id="titulo" name="titulo"
                                   value="{{ old('titulo', $clase->titulo) }}" required maxlength="200" autofocus
                                   placeholder="Ej: Clase 3 — Fracciones equivalentes">
                            @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label for="contenido" class="form-label">Contenido</label>
                            <textarea class="form-control @error('contenido') is-invalid @enderror" id="contenido" name="contenido"
                                      rows="12" maxlength="60000"
                                      placeholder="Explicación, consignas, lecturas...">{{ old('contenido', $clase->contenido) }}</textarea>
                            @error('contenido')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Los saltos de línea se respetan. Los enlaces que pegues en el texto se vuelven clickeables.</div>
                        </div>
                    </div>
                </div>

                {{-- Material --}}
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-paperclip me-1"></i>Material</h2>

                        @if ($clase->exists && $clase->recursos->isNotEmpty())
                            <p class="small text-muted mb-2">Material actual (marcá lo que quieras quitar):</p>
                            <ul class="list-group mb-4">
                                @foreach ($clase->recursos as $r)
                                    <li class="list-group-item d-flex align-items-center gap-2">
                                        <i class="bi {{ $r->icono }} fs-5"></i>
                                        <span class="flex-grow-1 text-truncate">{{ $r->nombre_visible }}</span>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" name="quitar_recursos[]" value="{{ $r->id }}" id="quitar{{ $r->id }}">
                                            <label class="form-check-label small text-danger" for="quitar{{ $r->id }}">Quitar</label>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mb-4">
                            <label for="archivos" class="form-label">Agregar archivos</label>
                            <input type="file" class="form-control @error('archivos.*') is-invalid @enderror @error('archivos') is-invalid @enderror"
                                   id="archivos" name="archivos[]" multiple>
                            @error('archivos.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @error('archivos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Hasta 10 archivos de 20 MB cada uno: PDF, Word, Excel, PowerPoint, imágenes, audio, video o ZIP.</div>
                        </div>

                        <label class="form-label">Enlaces y videos</label>
                        <div id="listaEnlaces">
                            @foreach (old('enlaces', [['url' => '', 'titulo' => '']]) as $i => $e)
                                <div class="row g-2 mb-2 fila-enlace">
                                    <div class="col-md-7">
                                        <input type="url" class="form-control @error('enlaces.' . $i . '.url') is-invalid @enderror"
                                               name="enlaces[{{ $i }}][url]" value="{{ $e['url'] ?? '' }}" placeholder="https://www.youtube.com/watch?v=...">
                                        @error('enlaces.' . $i . '.url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="enlaces[{{ $i }}][titulo]" value="{{ $e['titulo'] ?? '' }}"
                                               placeholder="Título (opcional)" maxlength="200">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarEnlace">
                            <i class="bi bi-plus-lg me-1"></i> Otro enlace
                        </button>
                        <div class="form-text">Los videos de YouTube se ven dentro de la clase.</div>
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
                                    <option value="{{ $u->id }}" @selected((string) old('unidad_id', $clase->unidad_id) === (string) $u->id)>{{ $u->titulo }}</option>
                                @endforeach
                            </select>
                            @error('unidad_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="fecha_publicacion" class="form-label">Publicar el</label>
                            <input type="datetime-local" class="form-control @error('fecha_publicacion') is-invalid @enderror"
                                   id="fecha_publicacion" name="fecha_publicacion"
                                   value="{{ old('fecha_publicacion', $clase->fecha_publicacion?->format('Y-m-d\TH:i')) }}">
                            @error('fecha_publicacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Vacío = se publica al guardar. Con fecha, los alumnos la ven desde ese momento.</div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="visible" name="visible" value="1"
                                   @checked(old('visible', $clase->exists ? $clase->visible : true))>
                            <label class="form-check-label" for="visible">Visible para los alumnos</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i> {{ $clase->exists ? 'Guardar cambios' : 'Publicar clase' }}
                        </button>
                        <a href="{{ $clase->exists ? route('clases.show', $clase) : route('aulas.show', $aula) }}"
                           class="btn btn-outline-secondary w-100 mt-2">Cancelar</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const lista = document.getElementById('listaEnlaces');
    document.getElementById('btnAgregarEnlace').addEventListener('click', () => {
        const i = lista.querySelectorAll('.fila-enlace').length;
        if (i >= 20) return;
        const fila = document.createElement('div');
        fila.className = 'row g-2 mb-2 fila-enlace';
        fila.innerHTML =
            '<div class="col-md-7"><input type="url" class="form-control" name="enlaces[' + i + '][url]" placeholder="https://..."></div>' +
            '<div class="col-md-5"><input type="text" class="form-control" name="enlaces[' + i + '][titulo]" placeholder="Título (opcional)" maxlength="200"></div>';
        lista.appendChild(fila);
        fila.querySelector('input').focus();
    });
});
</script>
@endpush
