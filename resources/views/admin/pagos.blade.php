@extends('layouts.app')

@section('titulo', 'Pagos — Administración')

@section('contenido')
    @php($pesos = fn ($v) => \App\Models\Plan::pesos($v))

    <div class="page-header">
        <h1><i class="bi bi-receipt text-primary me-2"></i>Pagos</h1>
        <a href="{{ route('admin.pagos.crear') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Registrar pago</a>
    </div>

    @include('admin._nav')

    <ul class="nav nav-tabs mb-3">
        @foreach (['pendiente' => 'Pendientes', 'aprobado' => 'Aprobados', 'rechazado' => 'Rechazados', 'cancelado' => 'Cancelados', 'todos' => 'Todos'] as $k => $t)
            <li class="nav-item"><a class="nav-link {{ $estado === $k ? 'active' : '' }}" href="{{ route('admin.pagos.index', ['estado' => $k]) }}">{{ $t }}</a></li>
        @endforeach
    </ul>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Fecha</th><th>Cliente</th><th>Detalle</th><th>Forma</th><th class="text-end">Importe</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($pagos as $p)
                        @php($ab = $p->abono)
                        <tr>
                            <td class="small text-nowrap">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <i class="bi {{ $ab->tipo === 'institucional' ? 'bi-building' : 'bi-person' }} me-1 text-muted"></i>{{ $ab->nombre }}
                                <div class="small text-muted">{{ $ab->usuario?->email ?? $ab->institucion?->email }}</div>
                            </td>
                            <td class="small">
                                {{ $p->detalle }}
                                @if ($p->observaciones)<div class="text-muted" style="white-space: pre-line">{{ \Illuminate\Support\Str::limit($p->observaciones, 120) }}</div>@endif
                            </td>
                            <td class="small">{{ $p->metodo_label }}</td>
                            <td class="text-end fw-semibold text-nowrap">{{ $pesos($p->importe) }}</td>
                            <td><span class="badge bg-{{ $p->estado_color }}">{{ $p->estado_label }}</span></td>
                            <td class="text-end text-nowrap">
                                @if ($p->comprobante_path)
                                    <a href="{{ route('abonos.comprobante', $p) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Comprobante"><i class="bi bi-paperclip"></i></a>
                                @endif
                                @if ($p->estado === 'pendiente')
                                    <form method="POST" action="{{ route('admin.pagos.aprobar', $p) }}" class="d-inline"
                                          data-confirmar="¿Aprobar {{ $pesos($p->importe) }} de {{ $ab->nombre }}? Se activa el abono.">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Aprobar</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRechazar"
                                            data-accion="{{ route('admin.pagos.rechazar', $p) }}" title="Rechazar"><i class="bi bi-x-lg"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay pagos en esta lista.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $pagos->links('pagination::bootstrap-5') }}</div>

    <div class="modal fade" id="modalRechazar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content" id="formRechazar">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">Rechazar pago</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label for="motivo" class="form-label">Motivo del rechazo</label>
                    <input type="text" class="form-control" id="motivo" name="motivo" maxlength="300" placeholder="Ej: no se encontró la transferencia">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rechazar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('modalRechazar').addEventListener('show.bs.modal', (e) => {
    document.getElementById('formRechazar').action = e.relatedTarget.dataset.accion;
});
</script>
@endpush
