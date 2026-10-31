@extends('layouts.app')

@section('titulo', 'Abono — ' . $institucion->nombre)

@section('contenido')
    @php($pesos = fn ($v) => \App\Models\Plan::pesos($v))

    <div class="page-header">
        <div>
            <a href="{{ route('instituciones.show', $institucion) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $institucion->nombre }}</a>
            <h1 class="mt-1"><i class="bi bi-credit-card text-primary me-2"></i>Abono de la institución</h1>
        </div>
    </div>

    <div class="row g-2 mb-4 text-center">
        <div class="col-md-4">
            <div class="card border-{{ $abono->estado_color }} h-100"><div class="card-body py-3">
                <span class="badge bg-{{ $abono->estado_color }} fs-6">{{ $abono->estado_label }}</span>
                <div class="small text-muted mt-2">
                    @if ($abono->vence_el)
                        {{ $abono->estaActivo() ? 'Vence el' : 'Venció el' }} {{ $abono->vence_el->format('d/m/Y') }}
                    @else
                        Sin abono contratado
                    @endif
                </div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body py-3">
                <div class="h3 mb-0">{{ $usados }} <span class="fs-6 text-muted">/ {{ (int) $abono->cupo_docentes }}</span></div>
                <div class="small text-muted">Docentes usando / contratados</div>
                @if ($abono->cupo_docentes)
                    <div class="progress mt-2" style="height: 6px">
                        <div class="progress-bar {{ $usados >= $abono->cupo_docentes ? 'bg-danger' : '' }}"
                             style="width: {{ min(100, round($usados / max(1, $abono->cupo_docentes) * 100)) }}%"></div>
                    </div>
                @endif
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body py-3">
                <div class="small text-muted mb-1">Precio por docente por mes</div>
                @foreach ($escalas as $e)
                    <div class="small d-flex justify-content-between"><span>{{ $e->rango_texto }}</span><strong>{{ $pesos($e->precio) }}</strong></div>
                @endforeach
            </div></div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            @if ($escalas->isEmpty())
                <div class="alert alert-warning">Todavía no hay precios cargados para abonos institucionales. Escribinos a {{ config('abonos.contacto') }}.</div>
            @elseif ($pendiente)
                <div class="alert alert-info d-flex flex-wrap align-items-center gap-2">
                    <div class="flex-grow-1">
                        <i class="bi bi-hourglass-split me-1"></i>
                        Hay un aviso de pago <strong>pendiente de confirmación</strong>: {{ $pendiente->detalle }} por {{ $pesos($pendiente->importe) }}.
                    </div>
                    <form method="POST" action="{{ route('abonos.cancelar', $pendiente) }}" data-confirmar="¿Cancelar este aviso de pago?">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Cancelar aviso</button>
                    </form>
                </div>
            @else
                <div class="card">
                    <div class="card-body p-4">
                        @if ($abono->estaActivo())
                            <ul class="nav nav-pills mb-4" role="tablist">
                                <li class="nav-item"><button class="nav-link active" type="button" data-concepto="renovacion">Renovar</button></li>
                                <li class="nav-item"><button class="nav-link" type="button" data-concepto="ampliacion">Ampliar cupo</button></li>
                            </ul>
                        @else
                            <h2 class="h6 fw-bold mb-3">Contratar abono</h2>
                        @endif

                        <form method="POST" action="{{ route('abonos.institucion.pagar', $institucion) }}" enctype="multipart/form-data"
                              id="formAbono" data-enviando="Enviando..." data-cotizar="{{ route('abonos.institucion.cotizar', $institucion) }}">
                            @csrf
                            <input type="hidden" name="concepto" id="concepto" value="renovacion">

                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="cupo_docentes" class="form-label" id="labelCupo">Cantidad de docentes</label>
                                    <input type="number" class="form-control @error('cupo_docentes') is-invalid @enderror" id="cupo_docentes" name="cupo_docentes"
                                           min="{{ max(1, $usados) }}" max="{{ config('abonos.cupo_maximo', 500) }}" required
                                           value="{{ old('cupo_docentes', max(1, $usados, (int) $abono->cupo_docentes)) }}">
                                    @error('cupo_docentes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text" id="ayudaCupo">Hoy hay {{ $usados }} docentes en la institución.</div>
                                </div>
                                <div class="col-sm-6" id="bloqueMeses">
                                    <label for="meses" class="form-label">Meses</label>
                                    <select class="form-select" id="meses" name="meses">
                                        @foreach ($meses as $m)
                                            <option value="{{ $m }}" @selected((int) old('meses', 1) === $m)>{{ $m }} {{ $m === 1 ? 'mes' : 'meses' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="bg-light rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Total a pagar</span>
                                    <span class="h4 fw-bold mb-0" id="totalPagar">—</span>
                                </div>
                                <div class="small text-muted" id="detallePrecio"></div>
                            </div>

                            @if ($mpHabilitado)
                                <button type="submit" class="btn btn-mp btn-lg w-100 mb-2" formaction="{{ route('mp.institucion', $institucion) }}" formnovalidate>
                                    <i class="bi bi-credit-card-2-front me-1"></i> Pagar con Mercado Pago
                                </button>
                                <div class="small text-muted text-center mb-3">Tarjeta, dinero en cuenta o efectivo. Se acredita al instante.</div>

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
            <div class="alert alert-light border small">
                <strong>¿Cómo se cuenta?</strong> Cada docente que se suma con el código ocupa un lugar.
                Los administradores que no dan clases no ocupan lugar. Las aulas personales de cada docente no se cobran a la institución.
            </div>
            @include('abonos._historial', ['pagos' => $abono->pagos])
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formAbono');
    if (!form) return;

    const concepto = document.getElementById('concepto');
    const cupo     = document.getElementById('cupo_docentes');
    const meses    = document.getElementById('meses');
    const cupoActual = {{ (int) $abono->cupo_docentes }};
    const mesesRestantes = {{ $abono->mesesRestantes() }};
    let timer;

    const cotizar = () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const url = new URL(form.dataset.cotizar);
            url.searchParams.set('concepto', concepto.value);
            url.searchParams.set('cupo', cupo.value || 1);
            url.searchParams.set('meses', meses.value);
            try {
                const r = await window.axios.get(url.toString());
                document.getElementById('totalPagar').textContent = r.data.texto;
                document.getElementById('detallePrecio').textContent = concepto.value === 'ampliacion'
                    ? (cupo.value - cupoActual) + ' docentes nuevos × ' + r.data.porDocente + ' × ' + mesesRestantes + ' mes(es) hasta el vencimiento'
                    : cupo.value + ' docentes × ' + r.data.porDocente + ' × ' + meses.value + ' mes(es)';
            } catch (e) {
                document.getElementById('totalPagar').textContent = '—';
            }
        }, 250);
    };

    document.querySelectorAll('[data-concepto]').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('[data-concepto]').forEach(x => x.classList.toggle('active', x === b));
        concepto.value = b.dataset.concepto;
        const amp = concepto.value === 'ampliacion';
        document.getElementById('bloqueMeses').classList.toggle('d-none', amp);
        document.getElementById('labelCupo').textContent = amp ? 'Nuevo total de docentes' : 'Cantidad de docentes';
        if (amp && parseInt(cupo.value, 10) <= cupoActual) cupo.value = cupoActual + 1;
        cotizar();
    }));

    cupo.addEventListener('input', cotizar);
    meses.addEventListener('change', cotizar);
    cotizar();
});
</script>
@endpush
