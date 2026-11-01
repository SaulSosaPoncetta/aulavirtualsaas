@extends('layouts.app')

@section('content')
@php($cs = $pago->suscripcion->clienteSistema)
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Pago #{{ $pago->id_pago }}</h3>
    <a href="{{ route('pagos.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">Detalle</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Cliente</span>
                    <a href="{{ route('clientes.show', $cs->cliente) }}">{{ $cs->cliente->nombre }}</a></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Email</span><span>{{ $cs->cliente->email }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Sistema</span><span>{{ $cs->sistema->nombre }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Qué paga</span>
                    <span>{{ $pago->detalleTexto() }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Monto</span>
                    <strong>{{ $pago->moneda }} {{ number_format($pago->monto, 2, ',', '.') }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Medio</span>
                    <span>{{ $pago->esTransferencia() ? 'Transferencia' : 'Mercado Pago' }}</span></li>
                @if($pago->esTransferencia())
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Referencia</span>
                    <code>{{ $pago->codigoReferencia() }}</code></li>
                @endif
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Estado</span><strong>{{ ucfirst($pago->estado) }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Vencimiento actual del servicio</span>
                    <span>{{ $pago->suscripcion->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</span></li>
                @if($pago->revisado_at)
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Revisado</span>
                    <span>{{ $pago->revisado_at->format('d/m/Y H:i') }} · {{ $pago->revisor?->name }}</span></li>
                @endif
                @if($pago->motivo_rechazo)
                <li class="list-group-item"><span class="text-muted">Motivo del rechazo:</span> {{ $pago->motivo_rechazo }}</li>
                @endif
            </ul>
        </div>
    </div>

    <div class="col-lg-6">
        @if($pago->esTransferencia())
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white">Comprobante del cliente</div>
            <div class="card-body">
                @if($pago->comprobante_path)
                    <div class="small text-muted mb-2">
                        Subido el {{ $pago->comprobante_subido_at->format('d/m/Y H:i') }}
                        @if($pago->fecha_transferencia) · transferencia del {{ $pago->fecha_transferencia->format('d/m/Y') }} @endif
                    </div>
                    @if($pago->nota_cliente)<div class="mb-2"><span class="text-muted">Comentario:</span> {{ $pago->nota_cliente }}</div>@endif
                    @if($pago->esImagenComprobante())
                        <a href="{{ route('pagos.comprobante', $pago) }}" target="_blank">
                            <img src="{{ route('pagos.comprobante', $pago) }}" class="img-fluid border rounded" alt="Comprobante">
                        </a>
                    @else
                        <a href="{{ route('pagos.comprobante', $pago) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Abrir {{ $pago->comprobante_nombre }}
                        </a>
                    @endif
                @else
                    <div class="text-muted">El cliente todavía no subió el comprobante.</div>
                @endif
            </div>
        </div>
        @endif

        @if($pago->estado === 'pendiente')
        <div class="card shadow-sm">
            <div class="card-header bg-white">Revisión</div>
            <div class="card-body">
                <form method="POST" action="{{ route('pagos.aprobar', $pago) }}" class="mb-3"
                      onsubmit="return confirm('¿Confirmás que el dinero ya está en tu cuenta? Se activa el servicio y se avisa al sistema.');">
                    @csrf
                    <button class="btn btn-success w-100"><i class="bi bi-check-lg me-1"></i>Aprobar y activar servicio</button>
                </form>

                <form method="POST" action="{{ route('pagos.rechazar', $pago) }}">
                    @csrf
                    <label class="form-label" for="motivo">Motivo del rechazo</label>
                    <textarea name="motivo" id="motivo" rows="2" class="form-control mb-2 @error('motivo') is-invalid @enderror" required>{{ old('motivo') }}</textarea>
                    @error('motivo')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                    <button class="btn btn-outline-danger w-100"><i class="bi bi-x-lg me-1"></i>Rechazar</button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
