@extends('layouts.app')

@section('titulo', 'Registrar pago — Administración')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-plus-circle text-primary me-2"></i>Registrar un pago</h1>
    </div>

    @include('admin._nav')

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">1. Buscá el docente o la institución</h2>
                    <form method="GET" class="d-flex gap-2 mb-3">
                        <input type="search" class="form-control" name="buscar" value="{{ $buscar }}" placeholder="Email, apellido, DNI o nombre de la institución" autofocus>
                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                    </form>

                    <div class="list-group">
                        @foreach ($resultados as $r)
                            <button type="button" class="list-group-item list-group-item-action elegir-cliente"
                                    data-tipo="{{ $r['tipo'] }}" data-id="{{ $r['id'] }}" data-nombre="{{ $r['nombre'] }}">
                                <div class="d-flex justify-content-between">
                                    <span><i class="bi {{ $r['tipo'] === 'institucional' ? 'bi-building' : 'bi-person' }} me-1"></i>{{ $r['nombre'] }}</span>
                                    @if ($r['abono']?->vence_el)
                                        <span class="badge bg-{{ $r['abono']->estado_color }}">{{ $r['abono']->vence_el->format('d/m/Y') }}</span>
                                    @else
                                        <span class="badge bg-secondary">Sin abono</span>
                                    @endif
                                </div>
                                <div class="small text-muted">{{ $r['detalle'] }}</div>
                            </button>
                        @endforeach
                        @if ($buscar !== '' && $resultados->isEmpty())
                            <p class="small text-muted mb-0">No se encontraron docentes ni instituciones.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">2. Datos del pago</h2>
                    <form method="POST" action="{{ route('admin.pagos.guardar') }}" id="formPago">
                        @csrf
                        <input type="hidden" name="tipo" id="tipo" value="{{ old('tipo') }}">
                        <input type="hidden" name="id" id="clienteId" value="{{ old('id') }}">

                        <div class="alert alert-light border mb-3" id="clienteElegido">
                            {{ old('id') ? 'Cliente seleccionado' : 'Elegí un cliente de la lista de la izquierda.' }}
                        </div>
                        @error('id')<div class="text-danger small mb-2">Elegí un cliente.</div>@enderror

                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <label for="meses" class="form-label">Meses</label>
                                <input type="number" class="form-control" id="meses" name="meses" min="1" max="24" value="{{ old('meses', 1) }}" required>
                            </div>
                            <div class="col-sm-4 d-none" id="bloqueCupo">
                                <label for="cupo_docentes" class="form-label">Docentes</label>
                                <input type="number" class="form-control @error('cupo_docentes') is-invalid @enderror" id="cupo_docentes" name="cupo_docentes" min="1" value="{{ old('cupo_docentes') }}">
                                @error('cupo_docentes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-4">
                                <label for="metodo" class="form-label">Forma de pago</label>
                                <select class="form-select" id="metodo" name="metodo">
                                    @foreach (\App\Models\Pago::METODOS as $k => $t)
                                        <option value="{{ $k }}" @selected(old('metodo', 'transferencia') === $k)>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="importe" class="form-label">Importe cobrado</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="importe" name="importe" min="0" step="0.01" value="{{ old('importe') }}" placeholder="Vacío = precio de lista">
                            </div>
                            <div class="form-text">Si lo dejás vacío se calcula con los precios vigentes. Poné 0 para una cortesía.</div>
                        </div>

                        <div class="mb-4">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <input type="text" class="form-control" id="observaciones" name="observaciones" maxlength="1000" value="{{ old('observaciones') }}">
                        </div>

                        <button type="submit" class="btn btn-success w-100" id="btnRegistrar" @disabled(! old('id'))>
                            <i class="bi bi-check2-circle me-1"></i> Registrar y activar abono
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.elegir-cliente').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('.elegir-cliente').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    document.getElementById('tipo').value = b.dataset.tipo;
    document.getElementById('clienteId').value = b.dataset.id;
    document.getElementById('clienteElegido').innerHTML = '<strong>' + b.dataset.nombre + '</strong> — abono ' + (b.dataset.tipo === 'institucional' ? 'institucional' : 'unipersonal');
    document.getElementById('bloqueCupo').classList.toggle('d-none', b.dataset.tipo !== 'institucional');
    document.getElementById('cupo_docentes').required = b.dataset.tipo === 'institucional';
    document.getElementById('btnRegistrar').disabled = false;
}));
@if (old('tipo') === 'institucional')
    document.getElementById('bloqueCupo').classList.remove('d-none');
@endif
</script>
@endpush
