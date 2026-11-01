@extends('layouts.app')

@section('titulo', 'Abono — ' . $institucion->nombre)

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('instituciones.show', $institucion) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $institucion->nombre }}</a>
            <h1 class="mt-1"><i class="bi bi-credit-card text-primary me-2"></i>Abono de la institución</h1>
        </div>
    </div>

    <div class="row g-2 mb-4 text-center">
        <div class="col-md-6">
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
        <div class="col-md-6">
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
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            @if ($panelPagos)
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-2">{{ $abono->estaActivo() ? 'Renovar o ampliar el abono' : 'Contratar abono' }}</h2>
                        <p class="text-muted small mb-3">
                            Elegís la cantidad de docentes{{ $abono->estaActivo() ? ', renovás o ampliás el cupo' : ' y los meses' }} en el sitio de MiGestión,
                            y pagás con Mercado Pago (tarjeta, dinero en cuenta, efectivo o débito automático) o por transferencia.
                            Cuando se acredita, el abono se actualiza solo.
                        </p>
                        <ul class="small text-muted">
                            <li>Hoy hay <strong>{{ $usados }}</strong> docentes en la institución: el cupo no puede ser menor.</li>
                            @if ($abono->cupo_docentes)<li>Cupo contratado: <strong>{{ (int) $abono->cupo_docentes }}</strong> docentes.</li>@endif
                        </ul>
                        <a href="{{ route('abonos.institucion.panel', $institucion) }}" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-credit-card-2-front me-1"></i> {{ $abono->estaActivo() ? 'Renovar o ampliar' : 'Contratar abono' }}
                        </a>
                        <div class="small text-muted text-center mt-2">Los precios y el total se muestran en la página de pago.</div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">El pago del abono no está disponible en este momento. Escribinos a {{ config('abonos.contacto') }}.</div>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="alert alert-light border small">
                <strong>¿Cómo se cuenta?</strong> Cada docente que se suma con el código ocupa un lugar.
                Los administradores que no dan clases no ocupan lugar. Las aulas personales de cada docente no se cobran a la institución.
            </div>
            <div class="alert alert-light border small">
                Cuando pagás, el sitio de pagos le avisa al Aula Virtual y el abono se actualiza en segundos.
                Si ya pagaste y no se refleja, escribinos a {{ config('abonos.contacto') }}.
            </div>
        </div>
    </div>
@endsection
