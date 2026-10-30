@extends('layouts.app')

@section('titulo', 'Administración')

@section('contenido')
    @php($pesos = fn ($v) => \App\Models\Plan::pesos($v))

    <div class="page-header">
        <h1><i class="bi bi-shield-lock text-primary me-2"></i>Administración</h1>
        <a href="{{ route('admin.pagos.crear') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Registrar pago</a>
    </div>

    @include('admin._nav')

    <div class="row g-3 mb-4">
        @foreach ([
            ['Ingresos del mes', $pesos($stats['ingresos_mes']), 'bi-cash-coin', 'success'],
            ['Pagos por revisar', $stats['pendientes'], 'bi-hourglass-split', $stats['pendientes'] ? 'danger' : 'secondary'],
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
                    <h2 class="h6 fw-bold mb-3">Ingresos de los últimos 6 meses</h2>
                    @php($max = max(1, $ingresos->max('total')))
                    @foreach ($ingresos as $i)
                        <div class="d-flex align-items-center gap-2 mb-2 small">
                            <span style="width: 70px">{{ $i['mes'] }}</span>
                            <div class="flex-grow-1 bg-light rounded" style="height: 18px">
                                <div class="bg-success rounded" style="height: 18px; width: {{ round($i['total'] / $max * 100) }}%"></div>
                            </div>
                            <span class="text-end fw-semibold" style="width: 100px">{{ $pesos($i['total']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-hourglass-split me-1 text-danger"></i>Pagos por revisar</h2>
                    @forelse ($ultimosPagos as $p)
                        <div class="d-flex justify-content-between small py-1 border-bottom">
                            <span>{{ $p->abono->nombre }} · {{ $p->detalle }}</span>
                            <strong>{{ $pesos($p->importe) }}</strong>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">No hay pagos pendientes.</p>
                    @endforelse
                    @if ($ultimosPagos->isNotEmpty())
                        <a href="{{ route('admin.pagos.index') }}" class="btn btn-sm btn-outline-primary mt-3">Revisar pagos</a>
                    @endif
                </div>
            </div>

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
