@extends('layouts.app')

@section('titulo', 'Mi abono')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-credit-card text-primary me-2"></i>Mi abono</h1>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            {{-- Estado --}}
            <div class="card mb-4 border-{{ $abono->estado_color }}">
                <div class="card-body">
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
            </div>

            {{-- Pagar (en el Panel de MiGestión) --}}
            @if ($panelPagos)
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-2">{{ $abono->estaActivo() ? 'Renovar / adelantar meses' : 'Activar mi abono' }}</h2>
                        <p class="text-muted small mb-3">
                            Vas a pagar en el sitio de MiGestión: Mercado Pago (tarjeta, dinero en cuenta, efectivo o débito automático) o transferencia.
                            Cuando se acredita, el abono se actualiza solo.
                        </p>
                        <a href="{{ route('abonos.mio.panel') }}" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-credit-card-2-front me-1"></i> {{ $abono->estaActivo() ? 'Renovar mi abono' : 'Pagar mi abono' }}
                        </a>
                        <div class="small text-muted text-center mt-2">Los precios y el total se muestran en la página de pago.</div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">El pago del abono no está disponible en este momento. Escribinos a {{ config('abonos.contacto') }}.</div>
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

            <div class="alert alert-light border small">
                <strong>¿Cómo se actualiza?</strong> Cuando pagás, el sitio de pagos le avisa al Aula Virtual y tu abono se actualiza en segundos.
                Si ya pagaste y no se refleja, escribinos a {{ config('abonos.contacto') }}.
            </div>
        </div>
    </div>
@endsection
