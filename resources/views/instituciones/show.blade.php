@extends('layouts.app')

@section('titulo', $institucion->nombre)

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('instituciones.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Instituciones</a>
            <h1 class="mt-1"><i class="bi bi-building text-primary me-2"></i>{{ $institucion->nombre }}</h1>
            <div class="small text-muted mt-1">
                {{ collect([$institucion->nivel_label, $institucion->localidad, $institucion->cue ? 'CUE ' . $institucion->cue : null])->filter()->implode(' · ') }}
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('instituciones.importar', $institucion) }}" class="btn btn-primary"><i class="bi bi-file-earmark-arrow-up me-1"></i> Cargar alumnos</a>
            <a href="{{ route('instituciones.edit', $institucion) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> Datos</a>
        </div>
    </div>

    <div class="row g-2 mb-4 text-center">
        <div class="col-4"><div class="card"><div class="card-body py-2"><div class="h4 mb-0">{{ $docentes->count() }}</div><div class="small text-muted">Docentes</div></div></div></div>
        <div class="col-4"><div class="card"><div class="card-body py-2"><div class="h4 mb-0">{{ $alumnos->total() }}</div><div class="small text-muted">Alumnos</div></div></div></div>
        <div class="col-4"><div class="card"><div class="card-body py-2"><div class="h4 mb-0">{{ $aulas->count() }}</div><div class="small text-muted">Aulas</div></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            {{-- Código de invitación --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-2"><i class="bi bi-key me-1"></i>Código para docentes</h2>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="codigo-aula flex-grow-1" id="codigoAula">{{ $institucion->codigo_invitacion }}</span>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnCopiar" title="Copiar"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <p class="small text-muted mb-3">
                        El docente se registra (o entra con su cuenta), va a <strong>Instituciones</strong> y lo ingresa en "Sumarme".
                        Sus aulas personales siguen siendo suyas.
                    </p>
                    <form method="POST" action="{{ route('instituciones.codigo.regenerar', $institucion) }}"
                          data-confirmar="¿Generar un código nuevo? El actual deja de funcionar (los docentes que ya se sumaron siguen).">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat me-1"></i> Nuevo código</button>
                    </form>
                </div>
            </div>

            {{-- Docentes --}}
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-workspace me-1"></i>Docentes</h2>
                    @foreach ($docentes as $d)
                        @php($esAdminD = $d['roles']->contains('admin'))
                        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                            <div class="flex-grow-1 min-w-0">
                                <div class="text-truncate">{{ $d['user']->nombre_completo }}</div>
                                <div class="small text-muted text-truncate">{{ $d['user']->email }}</div>
                            </div>
                            @if ($esAdminD)<span class="badge bg-primary">Admin</span>@endif
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown" aria-label="Opciones"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <form method="POST" action="{{ route('instituciones.admin.alternar', [$institucion, $d['user']]) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi {{ $esAdminD ? 'bi-person-dash' : 'bi-shield-check' }} me-2"></i>{{ $esAdminD ? 'Quitar administración' : 'Hacer administrador' }}
                                            </button>
                                        </form>
                                    </li>
                                    @unless ($esAdminD)
                                        <li>
                                            <form method="POST" action="{{ route('instituciones.miembros.quitar', [$institucion, $d['user'], 'docente']) }}"
                                                  data-confirmar="¿Quitar a {{ $d['user']->nombre_completo }} de la institución? Su cuenta y sus aulas no se borran.">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-x-circle me-2"></i>Quitar de la institución</button>
                                            </form>
                                        </li>
                                    @endunless
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            {{-- Aulas --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-journal-bookmark me-1"></i>Aulas de la institución</h2>
                    @forelse ($aulas as $a)
                        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                            <span class="rounded" style="width: 8px; height: 36px; background-color: {{ $a->color }}"></span>
                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ route('aulas.show', $a) }}" class="fw-semibold text-truncate d-block text-decoration-none">{{ $a->nombre }}</a>
                                <div class="small text-muted">{{ $a->titular?->nombre_completo }} · Ciclo {{ $a->ciclo_lectivo }}{{ $a->estado === 'archivada' ? ' · archivada' : '' }}</div>
                            </div>
                            <span class="small text-muted"><i class="bi bi-people me-1"></i>{{ $a->alumnos_count }}</span>
                            <a href="{{ route('libreta.index', $a) }}" class="btn btn-sm btn-outline-secondary" title="Calificaciones"><i class="bi bi-table"></i></a>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Todavía no hay aulas. Cuando un docente de la institución cree un aula y elija esta institución, aparece acá.</p>
                    @endforelse
                </div>
            </div>

            {{-- Alumnos --}}
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <h2 class="h6 fw-bold mb-0 me-auto"><i class="bi bi-people me-1"></i>Alumnos</h2>
                        <form method="GET" class="d-flex gap-2">
                            <input type="search" class="form-control form-control-sm" name="buscar" value="{{ $buscar }}" placeholder="Apellido, nombre, DNI o email">
                            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Alumno</th><th>DNI</th><th>Email</th><th>Último ingreso</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse ($alumnos as $al)
                                    <tr>
                                        <td>
                                            {{ $al->nombre_completo }}
                                            @if ($al->debe_cambiar_password)<span class="badge bg-warning text-dark ms-1" title="Todavía no cambió la contraseña inicial">Sin activar</span>@endif
                                        </td>
                                        <td>{{ $al->dni ?? '—' }}</td>
                                        <td class="small">{{ $al->email ?? '—' }}</td>
                                        <td class="small text-muted">{{ $al->ultimo_acceso_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                                        <td class="text-end text-nowrap">
                                            <form method="POST" action="{{ route('instituciones.alumnos.password', [$institucion, $al]) }}" class="d-inline"
                                                  data-confirmar="¿Restablecer la contraseña de {{ $al->nombre_completo }}? Va a quedar su DNI y tendrá que cambiarla al ingresar.">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-link" title="Restablecer contraseña al DNI"><i class="bi bi-key"></i></button>
                                            </form>
                                            <form method="POST" action="{{ route('instituciones.miembros.quitar', [$institucion, $al, 'alumno']) }}" class="d-inline"
                                                  data-confirmar="¿Quitar a {{ $al->nombre_completo }} de la institución? Su cuenta no se borra.">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-link text-danger" title="Quitar de la institución"><i class="bi bi-person-dash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">
                                        {{ $buscar !== '' ? 'No se encontraron alumnos.' : 'Todavía no hay alumnos. Cargalos desde una planilla con "Cargar alumnos".' }}
                                    </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $alumnos->links('pagination::bootstrap-5') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btnCopiar');
    btn?.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(document.getElementById('codigoAula').textContent.trim());
            btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            setTimeout(() => btn.innerHTML = '<i class="bi bi-clipboard"></i>', 1500);
        } catch (e) {}
    });
});
</script>
@endpush
