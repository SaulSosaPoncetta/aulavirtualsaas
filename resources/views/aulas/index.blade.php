@extends('layouts.app')

@section('titulo', 'Mis aulas')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-journal-bookmark text-primary me-2"></i>Mis aulas</h1>

        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalUnirse">
                <i class="bi bi-key me-1"></i> Unirme con código
            </button>
            @if (auth()->user()->esDocente() || auth()->user()->esSuperadmin())
                <a href="{{ route('aulas.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Crear aula
                </a>
            @endif
        </div>
    </div>

    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $estado === 'activa' ? 'active' : '' }}" href="{{ route('inicio') }}">Activas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $estado === 'archivada' ? 'active' : '' }}" href="{{ route('inicio', ['estado' => 'archivada']) }}">Archivadas</a>
        </li>
    </ul>

    @if ($aulas->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-journal-plus display-4 text-primary"></i>
                @if ($estado === 'archivada')
                    <p class="mt-3 mb-0 text-muted">No tenés aulas archivadas.</p>
                @elseif (auth()->user()->esDocente())
                    <h2 class="h5 mt-3">Todavía no tenés aulas</h2>
                    <p class="text-muted">Creá tu primera aula y compartí el código con tus alumnos.</p>
                    <a href="{{ route('aulas.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Crear aula</a>
                @else
                    <h2 class="h5 mt-3">Todavía no estás en ningún aula</h2>
                    <p class="text-muted">Pedile a tu docente el código del aula y usalo para unirte.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUnirse">
                        <i class="bi bi-key me-1"></i> Unirme con código
                    </button>
                @endif
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach ($aulas as $aula)
                @php($rol = $roles[$aula->id] ?? null)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('aulas.show', $aula) }}" class="text-decoration-none text-reset">
                        <div class="card card-aula h-100" style="border-top-color: {{ $aula->color }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h2 class="h5 card-title mb-0">{{ $aula->nombre }}</h2>
                                    @if ($rol === 'docente')
                                        <span class="badge bg-primary-subtle text-primary-emphasis">Docente</span>
                                    @elseif ($rol === 'alumno')
                                        <span class="badge bg-info-subtle text-info-emphasis">Alumno</span>
                                    @endif
                                </div>

                                <p class="text-muted small mb-3">
                                    @if ($aula->nombre_escuela)
                                        <i class="bi bi-building me-1"></i>{{ $aula->nombre_escuela }}<br>
                                    @endif
                                    <i class="bi bi-calendar3 me-1"></i>Ciclo {{ $aula->ciclo_lectivo }}
                                    @if ($aula->turno) · {{ $aula->turno }} @endif
                                </p>

                                <div class="d-flex justify-content-between align-items-center small">
                                    <span><i class="bi bi-person-workspace me-1"></i>{{ $aula->titular?->nombre_completo }}</span>
                                    <span class="text-muted"><i class="bi bi-people me-1"></i>{{ $aula->alumnos_count }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modal: unirse con código --}}
    <div class="modal fade" id="modalUnirse" tabindex="-1" aria-labelledby="modalUnirseTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('aulas.unirse') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUnirseTitulo"><i class="bi bi-key me-2"></i>Unirme a un aula</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label for="codigo" class="form-label">Código del aula</label>
                    <input type="text" class="form-control form-control-lg text-uppercase text-center fw-bold"
                           id="codigo" name="codigo" value="{{ old('codigo') }}" maxlength="10"
                           placeholder="K7M2QX" required autocomplete="off" style="letter-spacing: .3em">
                    <div class="form-text">Te lo pasa tu docente. Son 6 letras y números.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Unirme</button>
                </div>
            </form>
        </div>
    </div>
@endsection
