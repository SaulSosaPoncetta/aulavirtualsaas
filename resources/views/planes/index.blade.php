@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-tags me-2"></i>Planes y precios</h3>
    <a href="{{ route('planes.create', request()->only('sistema')) }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nuevo plan
    </a>
</div>

<form method="GET" class="mb-3">
    <div class="row g-2">
        <div class="col-md-4">
            <select name="sistema" class="form-select" onchange="this.form.submit()">
                <option value="">Todos los sistemas</option>
                @foreach($sistemas as $s)
                    <option value="{{ $s->id_sistema }}" @selected(request('sistema') == $s->id_sistema)>{{ $s->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Sistema</th>
                    <th>Plan</th>
                    <th>Código</th>
                    <th>Cobro</th>
                    <th class="text-end">Precio</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($planes as $plan)
                <tr>
                    <td>{{ $plan->sistema->nombre }}</td>
                    <td>
                        {{ $plan->nombre }}
                        @if($plan->descripcion)<div class="text-muted small">{{ $plan->descripcion }}</div>@endif
                    </td>
                    <td><code>{{ $plan->codigo }}</code></td>
                    <td>
                        {{ $plan->tipo === 'unico' ? 'Pago adelantado' : 'Débito automático' }}
                        <div class="text-muted small">{{ $plan->periodoTexto() }}</div>
                    </td>
                    <td class="text-end">
                        {{ $plan->moneda }} {{ number_format($plan->precio, 2, ',', '.') }}
                        @if($plan->cobro_por_unidad)
                            <div class="text-muted small">por {{ $plan->unidad_nombre ?: 'unidad' }}{{ $plan->esMensual() ? ' por mes' : '' }}
                                ({{ $plan->unidad_minima }}{{ $plan->unidad_maxima ? '–'.$plan->unidad_maxima : '+' }})</div>
                            @foreach($plan->escalas as $e)
                                <div class="text-muted small">{{ $e->desde }}{{ $e->hasta ? '–'.$e->hasta : '+' }}: {{ $plan->moneda }} {{ number_format($e->precio, 2, ',', '.') }}</div>
                            @endforeach
                        @elseif($plan->esMensual())
                            <div class="text-muted small">por mes</div>
                        @endif
                    </td>
                    <td>
                        @if($plan->activo)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('planes.edit', $plan) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Todavía no cargaste planes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
