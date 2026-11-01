@extends('layouts.publico')
@section('titulo', 'Pago por transferencia')

@section('contenido')
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card shadow-sm">
    <div class="card-header bg-marca text-white">
        <div class="small opacity-75">Pago por transferencia</div>
        <div class="fs-5 fw-semibold">{{ $sistema->nombre }}</div>
    </div>
    <div class="card-body p-4">

        @if($pago->estado === 'aprobado')
            <div class="text-center">
                <i class="bi bi-check-circle text-success fs-1"></i>
                <h5 class="mt-2">¡Pago acreditado!</h5>
                <p class="text-muted">Tu servicio ya está activo.</p>
                <a href="{{ $sistema->url_base }}" class="btn btn-marca">Volver a {{ $sistema->nombre }}</a>
            </div>

        @elseif($pago->estado === 'rechazado')
            <div class="text-center">
                <i class="bi bi-x-circle text-danger fs-1"></i>
                <h5 class="mt-2">No pudimos validar tu transferencia</h5>
                @if($pago->motivo_rechazo)<p class="text-muted">{{ $pago->motivo_rechazo }}</p>@endif
                <p class="text-muted small">Volvé al sistema y tocá "Pagar" para intentarlo de nuevo, o escribinos.</p>
                <a href="{{ $sistema->url_base }}" class="btn btn-marca">Volver a {{ $sistema->nombre }}</a>
            </div>

        @else
            <div class="mb-3">
                <div class="text-muted small">Importe a transferir</div>
                <div class="fs-3 fw-semibold">{{ $pago->moneda }} {{ number_format($pago->monto, 2, ',', '.') }}</div>
                <div class="text-muted small">{{ $pago->detalleTexto() }}</div>
            </div>

            <ul class="list-group mb-3">
                @foreach([
                    'Titular' => $datos['titular'],
                    'Banco' => $datos['banco'],
                    'CBU' => $datos['cbu'],
                    'Alias' => $datos['alias'],
                    'CUIT/CUIL' => $datos['cuit'],
                ] as $etiqueta => $valor)
                    @if($valor)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">{{ $etiqueta }}</span>
                        <span>
                            <strong class="dato-copiable">{{ $valor }}</strong>
                            @if(in_array($etiqueta, ['CBU', 'Alias']))
                                <button type="button" class="btn btn-sm btn-link btn-copiar" title="Copiar"><i class="bi bi-clipboard"></i></button>
                            @endif
                        </span>
                    </li>
                    @endif
                @endforeach
                <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                    <span class="text-muted">Concepto / referencia</span>
                    <span>
                        <strong class="dato-copiable">{{ $pago->codigoReferencia() }}</strong>
                        <button type="button" class="btn btn-sm btn-link btn-copiar" title="Copiar"><i class="bi bi-clipboard"></i></button>
                    </span>
                </li>
            </ul>
            <p class="small text-muted">Poné el código de referencia en el concepto de la transferencia: nos ayuda a identificar tu pago más rápido.</p>

            <hr>

            @if($pago->comprobante_path)
                <div class="alert alert-success small">
                    <i class="bi bi-check-circle me-1"></i>
                    Recibimos tu comprobante el {{ $pago->comprobante_subido_at->format('d/m/Y H:i') }}. Lo revisamos y activamos tu servicio; normalmente en menos de 24 horas hábiles.
                    Si te equivocaste de archivo, podés subir otro.
                </div>
            @endif

            <form method="POST" action="{{ $urlSubir }}" enctype="multipart/form-data">
                @csrf
                <h6>{{ $pago->comprobante_path ? 'Reemplazar comprobante' : 'Ya transferí: adjuntar comprobante' }}</h6>
                <div class="mb-3">
                    <label class="form-label" for="comprobante">Comprobante (JPG, PNG o PDF, hasta 5 MB)</label>
                    <input type="file" class="form-control" id="comprobante" name="comprobante" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="fecha_transferencia">Fecha de la transferencia (opcional)</label>
                    <input type="date" class="form-control" id="fecha_transferencia" name="fecha_transferencia" max="{{ now()->toDateString() }}">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="nota">Comentario (opcional)</label>
                    <input type="text" class="form-control" id="nota" name="nota" maxlength="500">
                </div>
                <button class="btn btn-marca w-100"><i class="bi bi-upload me-1"></i>Enviar comprobante</button>
            </form>
        @endif
    </div>
</div>

<script>
document.querySelectorAll('.btn-copiar').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const texto = btn.parentElement.querySelector('.dato-copiable').textContent.trim();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(texto);
            btn.innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
        }
    });
});
</script>
@endsection
