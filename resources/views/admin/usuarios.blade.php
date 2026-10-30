@extends('layouts.app')

@section('titulo', 'Usuarios — Administración')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-people text-primary me-2"></i>Usuarios</h1>
    </div>

    @include('admin._nav')

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6"><input type="search" class="form-control" name="buscar" value="{{ $buscar }}" placeholder="Nombre, apellido, email o DNI"></div>
        <div class="col-md-3">
            <select class="form-select" name="rol" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach (['docente' => 'Docentes', 'alumno' => 'Alumnos', 'superadmin' => 'Superadmin'] as $k => $t)
                    <option value="{{ $k }}" @selected($rol === $k)>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Buscar</button></div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Usuario</th><th>DNI</th><th>Rol</th><th>Aulas</th><th>Abono</th><th>Último ingreso</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $u)
                        <tr class="{{ $u->activo ? '' : 'table-secondary' }}">
                            <td>
                                {{ $u->nombre_completo }}
                                @unless ($u->activo)<span class="badge bg-dark ms-1">Desactivado</span>@endunless
                                <div class="small text-muted">{{ $u->email ?? 'sin email' }}</div>
                            </td>
                            <td class="small">{{ $u->dni ?? '—' }}</td>
                            <td class="small">{{ $u->getRoleNames()->map(fn ($r) => ucfirst($r))->implode(', ') ?: '—' }}</td>
                            <td class="small">
                                @if ($u->aulas_titular_count)<span title="Titular"><i class="bi bi-person-workspace"></i> {{ $u->aulas_titular_count }}</span>@endif
                                @if ($u->aulas_como_alumno_count)<span title="Alumno" class="ms-1"><i class="bi bi-backpack"></i> {{ $u->aulas_como_alumno_count }}</span>@endif
                            </td>
                            <td class="small">
                                @if ($u->abono)
                                    <span class="badge bg-{{ $u->abono->estado_color }}">{{ $u->abono->vence_el?->format('d/m/Y') ?? 'Sin abono' }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small text-muted">{{ $u->ultimo_acceso_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                            <td class="text-end text-nowrap">
                                @if ($u->dni)
                                    <form method="POST" action="{{ route('admin.usuarios.password', $u) }}" class="d-inline"
                                          data-confirmar="¿Restablecer la contraseña de {{ $u->nombre_completo }} a su DNI?">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-link" title="Restablecer contraseña al DNI"><i class="bi bi-key"></i></button>
                                    </form>
                                @endif
                                @if ($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.usuarios.activo', $u) }}" class="d-inline"
                                          data-confirmar="{{ $u->activo ? '¿Desactivar a ' . $u->nombre_completo . '? No va a poder ingresar.' : '¿Reactivar a ' . $u->nombre_completo . '?' }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-link {{ $u->activo ? 'text-danger' : 'text-success' }}" title="{{ $u->activo ? 'Desactivar' : 'Reactivar' }}">
                                            <i class="bi {{ $u->activo ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $usuarios->links('pagination::bootstrap-5') }}</div>
@endsection
