{{-- Historial de pagos del abono --}}
<div class="card">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3"><i class="bi bi-receipt me-1"></i>Historial de pagos</h2>
        @forelse ($pagos as $p)
            <div class="d-flex align-items-center gap-2 py-2 border-bottom small">
                <div class="flex-grow-1">
                    <div class="fw-semibold">{{ \App\Models\Plan::pesos($p->importe) }} <span class="fw-normal text-muted">· {{ $p->detalle }}</span></div>
                    <div class="text-muted">{{ $p->created_at->format('d/m/Y') }} · {{ $p->metodo_label }}</div>
                </div>
                @if ($p->comprobante_path)
                    <a href="{{ route('abonos.comprobante', $p) }}" target="_blank" class="btn btn-sm btn-link" title="Ver comprobante"><i class="bi bi-paperclip"></i></a>
                @endif
                <span class="badge bg-{{ $p->estado_color }}">{{ $p->estado_label }}</span>
            </div>
        @empty
            <p class="small text-muted mb-0">Todavía no hay pagos.</p>
        @endforelse
    </div>
</div>
