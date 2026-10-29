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

    <div class="row g-4">
        {{-- Columna principal --}}
        <div class="col-lg-8">
            @if ($aula->descripcion)
                <div class="card mb-4">
                    <div class="card-body">
                        <p class="mb-0" style="white-space: pre-line">{{ $aula->descripcion }}</p>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-collection display-5"></i>
                    <p class="mt-3 mb-0">Acá van a aparecer las clases, el material y las tareas del aula.</p>
                </div>
            </div>
        </div>

        {{-- Columna lateral --}}
        <div class="col-lg-4">
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
                    <h2 class="h6 fw-bold mb-3">
                        <i class="bi bi-people me-1"></i>Alumnos
                        <span class="badge bg-primary ms-1">{{ $alumnos->count() }}</span>
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

            @if (! $puedeEditar && $aula->docente_id != auth()->id())
                <form method="POST" action="{{ route('aulas.salir', $aula) }}" class="mt-3 text-center"
                      data-confirmar="¿Salir de esta aula? Para volver vas a necesitar el código.">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-link text-danger"><i class="bi bi-box-arrow-left me-1"></i>Salir del aula</button>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
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
