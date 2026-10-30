@extends('layouts.app')

@section('titulo', 'Abonos — Administración')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-calendar-check text-primary me-2"></i>Abonos</h1>
    </div>

    @include('admin._nav')

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input type="search" class="form-control" name="buscar" value="{{ $buscar }}" placeholder="Nombre o email"></div>
        <div class="col-md-3">
            <select class="form-select" name="tipo" onchange="this.form.submit()">
                <option value="">Todos los tipos</option>
                <option value="unipersonal" @selected($tipo === 'unipersonal')>Unipersonal</option>
                <option value="institucional" @selected($tipo === 'institucional')>Institucional</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="estado" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                @foreach (['activo' => 'Activo', 'por_vencer' => 'Por vencer', 'vencido' => 'Vencido', 'sin_abono' => 'Sin abono'] as $k => $t)
                    <option value="{{ $k }}" @selected($estado === $k)>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Filtrar</button></div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Cliente</th><th>Tipo</th><th>Estado</th><th>Vence</th><th>Cupo</th><th class="text-end">Ajuste manual</th></tr>
                </thead>
                <tbody>
                    @forelse ($abonos as $a)
                        <tr>
                            <td>
                                {{ $a->nombre }}
                                <div class="small text-muted">{{ $a->usuario?->email ?? $a->institucion?->localidad }}</div>
                            </td>
                            <td class="small">{{ $a->tipo === 'institucional' ? 'Institucional' : 'Unipersonal' }}</td>
                            <td><span class="badge bg-{{ $a->estado_color }}">{{ $a->estado_label }}</span></td>
                            <td class="small">{{ $a->vence_el?->format('d/m/Y') ?? '—' }}</td>
                            <td class="small">
                                @if ($a->tipo === 'institucional')
                                    {{ $a->docentesUsados() }} / {{ (int) $a->cupo_docentes }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.abonos.ajustar', $a) }}" class="d-inline-flex gap-1"
                                      data-confirmar="¿Guardar el ajuste del abono de {{ $a->nombre }}?">
                                    @csrf @method('PATCH')
                                    <input type="date" class="form-control form-control-sm" name="vence_el" value="{{ $a->vence_el?->format('Y-m-d') }}" style="width: 150px">
                                    @if ($a->tipo === 'institucional')
                                        <input type="number" class="form-control form-control-sm" name="cupo_docentes" value="{{ (int) $a->cupo_docentes }}" min="0" style="width: 80px" title="Cupo">
                                    @endif
                                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar"><i class="bi bi-check-lg"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay abonos con ese filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="small text-muted mt-2">El ajuste manual sirve para cortesías o correcciones. Para registrar un cobro usá "Registrar pago" así queda en el historial.</p>
@endsection
