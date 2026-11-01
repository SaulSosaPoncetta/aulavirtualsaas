@extends('layouts.publico')
@section('titulo', 'Pagar '.$sistema->nombre)

@section('contenido')
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card shadow-sm">
    <div class="card-header bg-marca text-white">
        <div class="small opacity-75">Estás pagando</div>
        <div class="fs-5 fw-semibold">{{ $sistema->nombre }}</div>
    </div>
    <div class="card-body p-4">
        <div class="mb-3">
            <div class="text-muted small">Cuenta</div>
            <div class="fw-semibold">{{ $nombreCuenta }}</div>
            <div class="text-muted small">{{ $cliente->email }}</div>
        </div>

        @if($suscripcion)
            <div class="alert alert-light border small">
                <strong>Estado actual:</strong>
                @switch($suscripcion->estado)
                    @case('activa') <span class="text-success">al día</span> @break
                    @case('vencida') <span class="text-danger">vencido</span> @break
                    @case('suspendida') <span class="text-danger">suspendido</span> @break
                    @default <span class="text-warning">pendiente de pago</span>
                @endswitch
                @if($suscripcion->fecha_vencimiento)
                    · vence el {{ $suscripcion->fecha_vencimiento->format('d/m/Y') }}
                @endif
                @if($suscripcion->cantidad > 1 && $suscripcion->planCatalogo?->cobro_por_unidad)
                    · cupo actual: {{ $suscripcion->cantidad }} {{ $suscripcion->planCatalogo->unidad_nombre ?: 'unidades' }}
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('pagar.iniciar') }}?{{ $queryFirmada }}" id="form-pago">
            @csrf

            @foreach($planes as $i => $plan)
                <label class="plan-opcion card mb-2 border">
                    <div class="card-body py-3 d-flex align-items-start">
                        @if($planes->count() > 1)
                            <input class="form-check-input mt-1 me-3" type="radio" name="id_plan" value="{{ $plan->id_plan }}"
                                   @checked(old('id_plan', $i === 0 ? $plan->id_plan : null) == $plan->id_plan)>
                        @else
                            <input type="hidden" name="id_plan" value="{{ $plan->id_plan }}">
                        @endif
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $plan->nombre }}</div>
                            @if($plan->descripcion)<div class="text-muted small">{{ $plan->descripcion }}</div>@endif
                            <div class="text-muted small">{{ $plan->periodoTexto() }}{{ $plan->tipo === 'recurrente' ? ' · débito automático' : '' }}</div>
                        </div>
                        <div class="text-end ms-3">
                            @if($plan->cobro_por_unidad && $plan->escalas->isNotEmpty())
                                <div class="small text-muted">desde</div>
                                <div class="fw-semibold">{{ $plan->moneda }} {{ number_format($plan->escalas->min('precio'), 2, ',', '.') }}</div>
                            @else
                                <div class="fw-semibold">{{ $plan->moneda }} {{ number_format($plan->precio, 2, ',', '.') }}</div>
                            @endif
                            <div class="text-muted small">
                                @if($plan->cobro_por_unidad) por {{ $plan->unidad_nombre ?: 'unidad' }}{{ $plan->esMensual() ? ' por mes' : '' }}
                                @elseif($plan->esMensual()) por mes
                                @endif
                            </div>
                        </div>
                    </div>
                </label>
            @endforeach

            {{-- Renovar o ampliar el cupo (solo si tiene un abono vigente del mismo plan por unidad) --}}
            <div class="mb-3 mt-3" id="grupo-concepto" style="display:none;">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="concepto" id="concepto-renovacion" value="renovacion" checked>
                    <label class="form-check-label" for="concepto-renovacion">Renovar el abono</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="concepto" id="concepto-ampliacion" value="ampliacion">
                    <label class="form-check-label" for="concepto-ampliacion">Ampliar el cupo (se paga solo la diferencia hasta el vencimiento)</label>
                </div>
            </div>

            <div class="mb-3 mt-3" id="grupo-meses" style="display:none;">
                <label class="form-label" for="meses">Meses a pagar</label>
                <select class="form-select" name="meses" id="meses"></select>
            </div>

            <div class="mb-3" id="grupo-cantidad" style="display:none;">
                <label class="form-label" for="cantidad" id="label-cantidad">Cantidad</label>
                <input type="number" min="1" class="form-control" name="cantidad" id="cantidad" value="{{ old('cantidad', $cantidadInicial ?: '') }}">
                <div class="form-text" id="ayuda-cantidad"></div>
            </div>

            <div class="small text-muted" id="precio-unitario"></div>

            <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                <span class="text-muted">Total a pagar</span>
                <span class="fs-4 fw-semibold" id="total">—</span>
            </div>

            <button type="submit" name="medio" value="mercadopago" class="btn btn-marca btn-lg w-100 mt-3" id="btn-mp">
                <i class="bi bi-credit-card me-1"></i>Pagar con Mercado Pago
            </button>
            @if($transferenciaActiva)
                <button type="submit" name="medio" value="transferencia" class="btn btn-outline-secondary btn-lg w-100 mt-2" id="btn-transf">
                    <i class="bi bi-bank me-1"></i>Pagar por transferencia bancaria
                </button>
                <div class="form-text text-center">Con transferencia, tu servicio se activa cuando acreditamos el pago.</div>
            @endif
        </form>
    </div>
</div>

<script>
(function () {
    const PLANES = @json($planesJs);
    const SUSC = @json($suscJs);

    const form = document.getElementById('form-pago');
    const grupoConcepto = document.getElementById('grupo-concepto');
    const grupoMeses = document.getElementById('grupo-meses');
    const grupoCantidad = document.getElementById('grupo-cantidad');
    const selMeses = document.getElementById('meses');
    const cantidad = document.getElementById('cantidad');
    const etiqueta = document.getElementById('label-cantidad');
    const ayuda = document.getElementById('ayuda-cantidad');
    const unitario = document.getElementById('precio-unitario');
    const total = document.getElementById('total');
    const botones = form.querySelectorAll('button[type=submit]');

    const dinero = (moneda, n) => moneda + ' ' + n.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    function planElegido() {
        const radio = form.querySelector('input[name="id_plan"]:checked');
        const id = parseInt(radio ? radio.value : form.querySelector('input[name="id_plan"]').value, 10);
        return PLANES.find(p => p.id === id);
    }

    function precioUnitario(p, n) {
        if (!p.porUnidad || p.escalas.length === 0) return p.precio;
        const e = p.escalas.find(e => n >= e.desde && (e.hasta === null || n <= e.hasta));
        return e ? e.precio : null;
    }

    function esAmpliacion() {
        const r = document.getElementById('concepto-ampliacion');
        return grupoConcepto.style.display !== 'none' && r.checked;
    }

    let planAnterior = null;

    function actualizar() {
        const p = planElegido();

        // ¿Puede ampliar? Abono vigente del mismo plan por unidad
        const puedeAmpliar = p.porUnidad && SUSC.vigente && SUSC.idPlan === p.id;
        grupoConcepto.style.display = puedeAmpliar ? '' : 'none';
        const ampliar = puedeAmpliar && document.getElementById('concepto-ampliacion').checked;

        // Meses (solo planes "por mes" con más de una opción, y al renovar)
        const verMeses = p.mensual && p.meses.length > 1 && !ampliar;
        grupoMeses.style.display = verMeses ? '' : 'none';
        selMeses.disabled = !verMeses;
        if (planAnterior !== p.id) {
            selMeses.innerHTML = p.meses.map(m => '<option value="' + m + '">' + m + (m === 1 ? ' mes' : ' meses') + '</option>').join('');
        }

        // Cantidad
        grupoCantidad.style.display = p.porUnidad ? '' : 'none';
        cantidad.disabled = !p.porUnidad;
        let n = 1;
        let monto = null;

        if (p.porUnidad) {
            etiqueta.textContent = ampliar ? 'Nuevo cupo total de ' + p.unidad : 'Cantidad de ' + p.unidad;
            const min = ampliar ? Math.max(p.min, SUSC.cantidad + 1) : p.min;
            ayuda.textContent = (p.max ? 'Entre ' + min + ' y ' + p.max + '.' : 'Mínimo ' + min + '.')
                + (ampliar ? ' Hoy tenés ' + SUSC.cantidad + '.' : '');
            cantidad.min = min;
            if (p.max) cantidad.max = p.max; else cantidad.removeAttribute('max');
            if (planAnterior !== p.id || !cantidad.value) {
                cantidad.value = Math.max(parseInt(cantidad.value || '0', 10), min);
            }
            n = parseInt(cantidad.value || '0', 10);
        }
        planAnterior = p.id;

        const unit = precioUnitario(p, n);
        unitario.textContent = '';

        if (unit !== null && n > 0) {
            const meses = verMeses ? parseInt(selMeses.value || p.meses[0], 10) : p.meses[0];
            if (ampliar) {
                const extra = n - SUSC.cantidad;
                monto = extra > 0 ? unit * extra * SUSC.mesesRestantes : null;
                unitario.textContent = extra > 0
                    ? extra + ' ' + p.unidad + ' nuevos × ' + dinero(p.moneda, unit) + ' × ' + SUSC.mesesRestantes + (SUSC.mesesRestantes === 1 ? ' mes' : ' meses') + ' hasta el vencimiento (' + SUSC.vence + ')'
                    : '';
            } else {
                monto = (p.porUnidad ? unit * n : unit) * (p.mensual ? meses : 1);
                if (p.porUnidad) unitario.textContent = dinero(p.moneda, unit) + ' por ' + p.unidad.replace(/s$/, '') + (p.mensual ? ' por mes' : '');
            }
        }

        total.textContent = monto !== null ? dinero(p.moneda, monto) : '—';
        botones.forEach(b => b.disabled = monto === null);
    }

    form.addEventListener('input', actualizar);
    form.addEventListener('change', actualizar);
    actualizar();
})();
</script>
@endsection
