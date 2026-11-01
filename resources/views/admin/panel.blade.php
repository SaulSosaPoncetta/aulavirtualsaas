@extends('layouts.app')

@section('titulo', 'Administración')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-shield-lock text-primary me-2"></i>Administración</h1>
        <a href="{{ config('panel.url') }}" target="_blank" rel="noopener" class="btn btn-primary"><i class="bi bi-box-arrow-up-right me-1"></i> Cobros en el Panel</a>
    </div>

    @include('admin._nav')

    <div class="row g-3 mb-4">
        @foreach ([
            ['Abonos vencidos', $stats['vencidos'], 'bi-exclamation-triangle', $stats['vencidos'] ? 'danger' : 'secondary'],
            ['Docentes con abono', $stats['activos_uni'], 'bi-person-workspace', 'primary'],
            ['Instituciones con abono', $stats['activos_inst'] . ' · ' . $stats['cupos'] . ' cupos', 'bi-building', 'primary'],
            ['Docentes registrados', $stats['docentes'], 'bi-people', 'secondary'],
            ['Alumnos registrados', $stats['alumnos'], 'bi-backpack', 'secondary'],
            ['Instituciones', $stats['instituciones'], 'bi-bank', 'secondary'],
            ['Aulas activas', $stats['aulas'], 'bi-journal-bookmark', 'secondary'],
        ] as [$titulo, $valor, $icono, $color])
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi {{ $icono }} fs-2 text-{{ $color }}"></i>
                        <div>
                            <div class="h5 fw-bold mb-0">{{ $valor }}</div>
                            <div class="small text-muted">{{ $titulo }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-wallet2 me-1 text-primary"></i>Cobros</h2>
                    <p class="small text-muted">Los precios, los pagos (Mercado Pago y transferencias), las aprobaciones y los avisos de vencimiento se gestionan en el Panel de MiGestión. Acá solo se ve el estado de cada abono.</p>
                    <a href="{{ config('panel.url') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Abrir el Panel de cobros</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-alarm me-1 text-warning"></i>Vencen en los próximos 10 días</h2>
                    @forelse ($porVencer as $a)
                        <div class="d-flex justify-content-between small py-1 border-bottom">
                            <span>
                                <i class="bi {{ $a->tipo === 'institucional' ? 'bi-building' : 'bi-person' }} me-1"></i>{{ $a->nombre }}
                                @if ($a->usuario?->email)<span class="text-muted">· {{ $a->usuario->email }}</span>@endif
                            </span>
                            <span>{{ $a->vence_el->format('d/m') }} ({{ $a->diasRestantes() }} d)</span>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">Ningún abono vence en los próximos días.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
