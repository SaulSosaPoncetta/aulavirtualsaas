@extends('layouts.app')

@section('titulo', $aula->nombre)

@section('contenido')
    {{-- Encabezado del aula --}}
    <div class="aula-portada rounded-3 p-4 mb-4 text-white" style="background-color: {{ $aula->color }}">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <a href="{{ route('inicio') }}" class="text-white-50 small text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i>Mis aulas
                </a>
                <h1 class="h3 fw-bold mt-2 mb-1">{{ $aula->nombre }}</h1>
                <p class="mb-0 opacity-75">
                    @if ($aula->nombre_escuela){{ $aula->nombre_escuela }} · @endif
                    Ciclo {{ $aula->ciclo_lectivo }}
                    @if ($aula->turno) · {{ $aula->turno }} @endif
                </p>
                @if ($aula->estado === 'archivada')
                    <span class="badge bg-light text-dark mt-2"><i class="bi bi-archive me-1"></i>Archivada</span>
                @endif
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-start">
            <a href="{{ route('muro.index', $aula) }}" class="btn btn-light btn-sm position-relative">
                <i class="bi bi-megaphone me-1"></i> Muro
                @if ($nuevosMuro)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $nuevosMuro }}</span>
                @endif
            </a>
            <a href="{{ route('libreta.index', $aula) }}" class="btn btn-light btn-sm">
                <i class="bi bi-table me-1"></i> {{ $puedeEditar ? 'Calificaciones' : 'Mis notas' }}
            </a>
            @if ($puedeEditar)
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear me-1"></i> Opciones
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('aulas.edit', $aula) }}"><i class="bi bi-pencil me-2"></i>Editar datos</a></li>
                        <li>
                            <form method="POST" action="{{ route('aulas.archivar', $aula) }}"
                                  data-confirmar="{{ $aula->estado === 'activa' ? '¿Archivar esta aula? Va a quedar en solo lectura en Archivadas.' : '¿Volver a activar esta aula?' }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="dropdown-item">
                                    <i class="bi {{ $aula->estado === 'activa' ? 'bi-archive' : 'bi-arrow-counterclockwise' }} me-2"></i>
                                    {{ $aula->estado === 'activa' ? 'Archivar' : 'Reactivar' }}
                                </button>
                            </form>
                        </li>
                        @if ($aula->docente_id == auth()->id() || auth()->user()->esSuperadmin())
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('aulas.destroy', $aula) }}"
                                      data-confirmar="¿Eliminar el aula {{ $aula->nombre }}? Esta acción no se puede deshacer.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Eliminar aula</button>
                                </form>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Columna principal: contenido --}}
        <div class="col-lg-8">
            @if ($aula->descripcion)
                <div class="card mb-4">
                    <div class="card-body">
                        <p class="mb-0" style="white-space: pre-line">{{ $aula->descripcion }}</p>
                    </div>
                </div>
            @endif

            @if ($ultimoAviso)
                <a href="{{ route('muro.index', $aula) }}#pub{{ $ultimoAviso->id }}" class="card mb-4 text-decoration-none text-reset border-primary-subtle">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <i class="bi {{ $ultimoAviso->fijada ? 'bi-pin-angle-fill' : 'bi-megaphone' }} fs-4 text-primary"></i>
                        <div class="flex-grow-1 min-w-0">
                            <div class="small text-muted mb-1">
                                {{ $ultimoAviso->fijada ? 'Aviso fijado' : 'Último en el muro' }} ·
                                {{ $ultimoAviso->autor?->nombre_completo }} · {{ $ultimoAviso->created_at->format('d/m H:i') }}
                            </div>
                            <div class="text-truncate-3">{{ \Illuminate\Support\Str::limit($ultimoAviso->texto, 280) }}</div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </a>
            @endif

            @if ($puedeEditar && $aula->estado === 'activa')
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="{{ route('clases.create', $aula) }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Nueva clase
                    </a>
                    <a href="{{ route('tareas.create', $aula) }}" class="btn btn-primary">
                        <i class="bi bi-clipboard-plus me-1"></i> Nueva tarea
                    </a>
                    <a href="{{ route('evaluaciones.create', $aula) }}" class="btn btn-primary">
                        <i class="bi bi-ui-checks me-1"></i> Nueva evaluación
                    </a>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalUnidad"
                            data-accion="{{ route('unidades.store', $aula) }}" data-metodo="POST" data-titulo="" data-descripcion="" data-visible="1">
                        <i class="bi bi-folder-plus me-1"></i> Nueva unidad
                    </button>
                </div>
            @endif

            @php
                $general       = $clasesPorUnidad->get(0, collect());
                $generalTareas = $tareasPorUnidad->get(0, collect());
                $generalEvals  = $evaluacionesPorUnidad->get(0, collect());
                $hayContenido  = $unidades->isNotEmpty() || $general->isNotEmpty() || $generalTareas->isNotEmpty() || $generalEvals->isNotEmpty();
            @endphp

            @unless ($hayContenido)
                <div class="card">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-collection display-5"></i>
                        <p class="mt-3 mb-0">
                            @if ($puedeEditar)
                                Todavía no hay contenido. Creá una unidad y cargá tu primera clase.
                            @else
                                Tu docente todavía no publicó contenido.
                            @endif
                        </p>
                    </div>
                </div>
            @endunless

            {{-- Clases sin unidad --}}
            @if ($general->isNotEmpty() || $generalTareas->isNotEmpty() || $generalEvals->isNotEmpty())
                <div class="card mb-3">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-pin-angle me-1"></i>General</div>
                    <div class="list-group list-group-flush">
                        @foreach ($general as $clase)
                            @include('clases._item', ['clase' => $clase])
                        @endforeach
                        @foreach ($generalTareas as $tarea)
                            @include('tareas._item', ['tarea' => $tarea])
                        @endforeach
                        @foreach ($generalEvals as $evaluacion)
                            @include('evaluaciones._item', ['evaluacion' => $evaluacion])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Unidades --}}
            @foreach ($unidades as $unidad)
                @php
                    $clasesUnidad = $clasesPorUnidad->get($unidad->id, collect());
                    $tareasUnidad = $tareasPorUnidad->get($unidad->id, collect());
                    $evalsUnidad  = $evaluacionesPorUnidad->get($unidad->id, collect());
                @endphp
                <div class="card mb-3 {{ $unidad->visible ? '' : 'opacity-75' }}">
                    <div class="card-header bg-white d-flex align-items-center gap-2">
                        <div class="flex-grow-1">
                            <span class="fw-bold"><i class="bi bi-folder2-open me-1 text-primary"></i>{{ $unidad->titulo }}</span>
                            @unless ($unidad->visible)
                                <span class="badge bg-secondary ms-1"><i class="bi bi-eye-slash me-1"></i>Oculta</span>
                            @endunless
                        </div>

                        @if ($puedeEditar)
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('clases.create', ['aula' => $aula, 'unidad' => $unidad->id]) }}" class="btn btn-outline-primary"
                                   data-bs-toggle="tooltip" title="Agregar clase a esta unidad"><i class="bi bi-plus-lg"></i></a>
                                <a href="{{ route('tareas.create', ['aula' => $aula, 'unidad' => $unidad->id]) }}" class="btn btn-outline-primary"
                                   data-bs-toggle="tooltip" title="Agregar tarea a esta unidad"><i class="bi bi-clipboard-plus"></i></a>
                                <a href="{{ route('evaluaciones.create', ['aula' => $aula, 'unidad' => $unidad->id]) }}" class="btn btn-outline-primary"
                                   data-bs-toggle="tooltip" title="Agregar evaluación a esta unidad"><i class="bi bi-ui-checks"></i></a>
                                <form method="POST" action="{{ route('unidades.mover', [$unidad, 'arriba']) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-outline-secondary" @disabled($loop->first) title="Subir"><i class="bi bi-arrow-up"></i></button>
                                </form>
                                <form method="POST" action="{{ route('unidades.mover', [$unidad, 'abajo']) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-outline-secondary" @disabled($loop->last) title="Bajar"><i class="bi bi-arrow-down"></i></button>
                                </form>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalUnidad"
                                        data-accion="{{ route('unidades.update', $unidad) }}" data-metodo="PUT"
                                        data-titulo="{{ $unidad->titulo }}" data-descripcion="{{ $unidad->descripcion }}"
                                        data-visible="{{ $unidad->visible ? 1 : 0 }}" title="Editar unidad">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('unidades.destroy', $unidad) }}"
                                      data-confirmar="¿Eliminar la unidad {{ $unidad->titulo }}? Sus clases no se borran: pasan a General.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Eliminar unidad"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        @endif
                    </div>

                    @if ($unidad->descripcion)
                        <div class="card-body py-2 small text-muted border-bottom" style="white-space: pre-line">{{ $unidad->descripcion }}</div>
                    @endif

                    <div class="list-group list-group-flush">
                        @foreach ($clasesUnidad as $clase)
                            @include('clases._item', ['clase' => $clase])
                        @endforeach
                        @foreach ($tareasUnidad as $tarea)
                            @include('tareas._item', ['tarea' => $tarea])
                        @endforeach
                        @foreach ($evalsUnidad as $evaluacion)
                            @include('evaluaciones._item', ['evaluacion' => $evaluacion])
                        @endforeach
                        @if ($clasesUnidad->isEmpty() && $tareasUnidad->isEmpty() && $evalsUnidad->isEmpty())
                            <div class="list-group-item small text-muted">Sin contenido todavía.</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Columna lateral --}}
        <div class="col-lg-4">
            @if ($pendientes->isNotEmpty())
                <div class="card mb-4 border-warning">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-alarm me-1 text-warning"></i>Próximas entregas</h2>
                        <ul class="list-unstyled mb-0">
                            @foreach ($pendientes as $p)
                                <li class="mb-2">
                                    <a href="{{ $p['url'] }}" class="fw-semibold d-block text-truncate">{{ $p['titulo'] }}</a>
                                    <span class="small text-muted">{{ $p['tipo'] }} · vence el {{ $p['fecha']->format('d/m H:i') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if ($puedeEditar)
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-key me-1"></i>Código del aula</h2>

                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="codigo-aula flex-grow-1 {{ $aula->codigo_activo ? '' : 'text-decoration-line-through text-muted' }}"
                                  id="codigoAula">{{ $aula->codigo_acceso }}</span>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnCopiar"
                                    data-bs-toggle="tooltip" title="Copiar código">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>

                        <p class="small text-muted mb-3">
                            {{ $aula->codigo_activo
                                ? 'Tus alumnos se unen con este código desde "Unirme con código" o al registrarse.'
                                : 'El código está deshabilitado: nadie puede unirse.' }}
                        </p>

                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('aulas.codigo.alternar', $aula) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $aula->codigo_activo ? 'btn-outline-secondary' : 'btn-success' }}">
                                    <i class="bi {{ $aula->codigo_activo ? 'bi-pause-circle' : 'bi-play-circle' }} me-1"></i>
                                    {{ $aula->codigo_activo ? 'Deshabilitar' : 'Habilitar' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('aulas.codigo.regenerar', $aula) }}"
                                  data-confirmar="¿Generar un código nuevo? El actual dejará de funcionar.">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrow-repeat me-1"></i> Nuevo código
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-workspace me-1"></i>Docentes</h2>
                    <ul class="list-unstyled mb-0">
                        @foreach ($docentes as $d)
                            <li class="d-flex align-items-center mb-2">
                                <i class="bi bi-person-circle fs-5 text-primary me-2"></i>
                                <span>{{ $d->nombre_completo }}</span>
                                @if ($d->id == $aula->docente_id)
                                    <span class="badge bg-light text-dark ms-auto">Titular</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3 d-flex align-items-center">
                        <span><i class="bi bi-people me-1"></i>Alumnos <span class="badge bg-primary ms-1">{{ $alumnos->count() }}</span></span>
                        @if ($alumnosInstitucion->isNotEmpty())
                            <button type="button" class="btn btn-sm btn-outline-primary ms-auto" data-bs-toggle="modal" data-bs-target="#modalAlumnosInst">
                                <i class="bi bi-person-plus me-1"></i>De la institución
                            </button>
                        @endif
                    </h2>

                    @forelse ($alumnos as $a)
                        <div class="d-flex align-items-center py-1 border-bottom">
                            <i class="bi bi-person fs-5 text-muted me-2"></i>
                            <div class="flex-grow-1">
                                <div>{{ $a->nombre_completo }}</div>
                                @if ($puedeEditar)
                                    <div class="small text-muted">{{ $a->email ?? ('DNI ' . $a->dni) }}</div>
                                @endif
                            </div>
                            @if ($puedeEditar)
                                <form method="POST" action="{{ route('aulas.miembros.baja', [$aula, $a]) }}"
                                      data-confirmar="¿Quitar a {{ $a->nombre_completo }} del aula?">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-link text-danger" data-bs-toggle="tooltip" title="Quitar del aula">
                                        <i class="bi bi-person-dash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted small mb-0">
                            Todavía no hay alumnos.
                            @if ($puedeEditar) Compartí el código para que se unan. @endif
                        </p>
                    @endforelse
                </div>
            </div>

            @if (! $puedeEditar && $aula->esMiembro(auth()->user()))
                <form method="POST" action="{{ route('aulas.salir', $aula) }}" class="mt-3 text-center"
                      data-confirmar="¿Salir de esta aula? Para volver vas a necesitar el código.">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-link text-danger"><i class="bi bi-box-arrow-left me-1"></i>Salir del aula</button>
                </form>
            @endif
        </div>
    </div>
    @if ($alumnosInstitucion->isNotEmpty())
        {{-- Modal: agregar alumnos de la institución --}}
        <div class="modal fade" id="modalAlumnosInst" tabindex="-1" aria-labelledby="modalAlumnosInstTitulo" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <form method="POST" action="{{ route('aulas.alumnos.agregar', $aula) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAlumnosInstTitulo"><i class="bi bi-person-plus me-2"></i>Alumnos de {{ $aula->institucion->nombre }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="search" class="form-control mb-3" id="filtroAlumnosInst" placeholder="Buscar por apellido, nombre o DNI">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="todosAlumnosInst">
                            <label class="form-check-label fw-semibold" for="todosAlumnosInst">Seleccionar todos los visibles</label>
                        </div>
                        <div class="list-group" id="listaAlumnosInst">
                            @foreach ($alumnosInstitucion as $ai)
                                <label class="list-group-item d-flex gap-2 align-items-center" data-buscar="{{ mb_strtolower($ai->apellido . ' ' . $ai->name . ' ' . $ai->dni) }}">
                                    <input class="form-check-input mt-0" type="checkbox" name="alumnos[]" value="{{ $ai->id }}">
                                    <span class="flex-grow-1">{{ $ai->nombre_completo }}</span>
                                    <span class="small text-muted">{{ $ai->dni }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agregar al aula</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($puedeEditar)
        {{-- Modal: crear / editar unidad --}}
        <div class="modal fade" id="modalUnidad" tabindex="-1" aria-labelledby="modalUnidadTitulo" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content" id="formUnidad">
                    @csrf
                    <input type="hidden" name="_method" value="POST" id="unidadMetodo">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalUnidadTitulo"><i class="bi bi-folder2-open me-2"></i>Unidad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="unidadTitulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="unidadTitulo" name="titulo" required maxlength="150"
                                   placeholder="Ej: Unidad 1 — Números racionales">
                        </div>
                        <div class="mb-3">
                            <label for="unidadDescripcion" class="form-label">Descripción <span class="text-muted small">(opcional)</span></label>
                            <textarea class="form-control" id="unidadDescripcion" name="descripcion" rows="3" maxlength="2000"></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="unidadVisible" name="visible" value="1">
                            <label class="form-check-label" for="unidadVisible">Visible para los alumnos</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Modal de unidad: completa el formulario según el botón (crear / editar)
    const modalUnidad = document.getElementById('modalUnidad');
    if (modalUnidad) {
        modalUnidad.addEventListener('show.bs.modal', (e) => {
            const b = e.relatedTarget;
            document.getElementById('formUnidad').action = b.dataset.accion;
            document.getElementById('unidadMetodo').value = b.dataset.metodo;
            document.getElementById('unidadTitulo').value = b.dataset.titulo || '';
            document.getElementById('unidadDescripcion').value = b.dataset.descripcion || '';
            document.getElementById('unidadVisible').checked = b.dataset.visible === '1';
            document.getElementById('modalUnidadTitulo').innerHTML =
                '<i class="bi bi-folder2-open me-2"></i>' + (b.dataset.metodo === 'PUT' ? 'Editar unidad' : 'Nueva unidad');
        });
    }

    // Filtro y "seleccionar todos" de alumnos de la institución
    const filtro = document.getElementById('filtroAlumnosInst');
    if (filtro) {
        const filas = [...document.querySelectorAll('#listaAlumnosInst [data-buscar]')];
        filtro.addEventListener('input', () => {
            const t = filtro.value.trim().toLowerCase();
            filas.forEach(f => f.classList.toggle('d-none', t !== '' && !f.dataset.buscar.includes(t)));
        });
        document.getElementById('todosAlumnosInst').addEventListener('change', (e) => {
            filas.filter(f => !f.classList.contains('d-none')).forEach(f => f.querySelector('input').checked = e.target.checked);
        });
    }

    const btn = document.getElementById('btnCopiar');
    if (!btn) return;
    btn.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(document.getElementById('codigoAula').textContent.trim());
            btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 1500);
        } catch (e) {
            // Sin permiso de portapapeles: el código queda visible para copiarlo a mano
        }
    });
});
</script>
@endpush
