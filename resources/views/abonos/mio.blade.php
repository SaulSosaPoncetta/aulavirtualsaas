@extends('layouts.app')

@section('titulo', 'Mi abono')

@section('contenido')
    @php($pesos = fn ($v) => \App\Models\Plan::pesos($v))

    <div class="page-header">
        <h1><i class="bi bi-credit-card text-primary me-2"></i>Mi abono</h1>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            {{-- Estado --}}
            <div class="card mb-4 border-{{ $abono->estado_color }}">
                <div class="card-body d-flex flex-wrap align-items-center gap-3">
                    <div class="flex-grow-1">
                        <div class="small text-muted">Abono unipersonal</div>
                        <div class="h4 fw-bold mb-1">
                            <span class="badge bg-{{ $abono->estado_color }}">{{ $abono->estado_label }}</span>
                        </div>
                        <div class="small">
                            @if ($abono->vence_el)
                                {{ $abono->estaActivo() ? 'Vence el' : 'Venció el' }} <strong>{{ $abono->vence_el->format('d/m/Y') }}</strong>
                                @if ($abono->estaActivo()) ({{ $abono->diasRestantes() }} días) @endif
                            @else
                                Todavía no tenés un abono activo. Pagalo para crear tus aulas personales.
                            @endif
                        </div>
                    </div>
                    @if ($plan)
                        <div class="text-end">
                            <div class="h3 fw-bold mb-0">{{ $pesos($plan->precio) }}</div>
                            <div class="small text-muted">por mes</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Pagar --}}
            @if (! $plan)
                <div class="alert alert-warning">Todavía no hay un precio cargado para el abono. Escribinos a {{ config('abonos.contacto') }}.</div>
            @elseif ($pendiente)
                <div class="alert alert-info d-flex flex-wrap align-items-center gap-2">
                    <div class="flex-grow-1">
                        <i class="bi bi-hourglass-split me-1"></i>
                        Tenés un aviso de pago <strong>pendiente de confirmación</strong>: {{ $pendiente->detalle }} por {{ $pesos($pendiente->importe) }}
                        ({{ $pendiente->metodo_label }}, {{ $pendiente->created_at->format('d/m/Y') }}).
                    </div>
                    <form method="POST" action="{{ route('abonos.cancelar', $pendiente) }}" data-confirmar="¿Cancelar este aviso de pago?">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Cancelar aviso</button>
                    </form>
                </div>
            @else
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-3">{{ $abono->estaActivo() ? 'Renovar / adelantar meses' : 'Activar mi abono' }}</h2>

                        <form method="POST" action="{{ route('abonos.mio.pagar') }}" enctype="multipart/form-data" data-enviando="Enviando...">
                            @csrf
                            <label class="form-label">¿Cuántos meses?</label>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach ($meses as $m)
                                    <input type="radio" class="btn-check" name="meses" id="m{{ $m }}" value="{{ $m }}" data-importe="{{ $pesos($plan->precio * $m) }}"
                                           @checked((int) old('meses', 1) === $m)>
                                    <label class="btn btn-outline-primary" for="m{{ $m }}">{{ $m }} {{ $m === 1 ? 'mes' : 'meses' }}</label>
                                @endforeach
                            </div>

                            <div class="bg-light rounded p-3 mb-3 d-flex justify-content-between align-items-center">
                                <span>Total a pagar</span>
                                <span class="h4 fw-bold mb-0" id="totalPagar">{{ $pesos($plan->precio * (int) old('meses', 1)) }}</span>
                            </div>

                            @if ($mpHabilitado)
                                <button type="submit" class="btn btn-mp btn-lg w-100 mb-2" formaction="{{ route('mp.mio') }}" formnovalidate>
                                    <i class="bi bi-credit-card-2-front me-1"></i> Pagar con Mercado Pago
                                </button>
                                <div class="small text-muted text-center mb-3">Tarjeta, dinero en cuenta o efectivo. Se activa al instante.</div>

                                <button class="btn btn-link w-100 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#pagoManual">
                                    Prefiero pagar por transferencia
                                </button>
                                <div class="collapse {{ $errors->any() ? 'show' : '' }}" id="pagoManual">
                            @endif

                            @include('abonos._transferencia')

                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send me-1"></i> Avisar que pagué</button>

                            @if ($mpHabilitado)
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            @if ($instituciones->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-2"><i class="bi bi-building me-1"></i>Mis instituciones</h2>
                        <p class="small text-muted">Las aulas de cada institución las cubre su propio abono; tu abono unipersonal es para tus aulas personales.</p>
                        @foreach ($instituciones as $inst)
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span>{{ $inst->nombre }}</span>
                                @if ($inst->abonoActivo())
                                    <span class="text-success"><i class="bi bi-check-circle me-1"></i>Al día</span>
                                @else
                                    <span class="text-danger"><i class="bi bi-x-circle me-1"></i>Sin abono</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @include('abonos._historial', ['pagos' => $abono->pagos])
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('input[name="meses"]').forEach(r => r.addEventListener('change', () => {
    document.getElementById('totalPagar').textContent = r.dataset.importe;
}));
</script>
@endpush
