@extends('layouts.app')

@section('titulo', 'Precios — Administración')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-tags text-primary me-2"></i>Precios</h1>
    </div>

    @include('admin._nav')

    <p class="small text-muted">Los cambios se aplican a los próximos pagos y se muestran en la landing. Los pagos ya hechos no cambian.</p>

    <div class="row g-4">
        {{-- Unipersonal --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-workspace me-1"></i>Abono unipersonal (por mes)</h2>
                    @foreach ($unipersonal as $p)
                        <form method="POST" action="{{ route('admin.planes.update', $p) }}" class="mb-3">
                            @csrf @method('PUT')
                            <div class="mb-2">
                                <label class="form-label small mb-1">Nombre</label>
                                <input type="text" class="form-control form-control-sm" name="nombre" value="{{ $p->nombre }}" required maxlength="100">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Descripción (se ve en la landing)</label>
                                <textarea class="form-control form-control-sm" name="descripcion" rows="2" maxlength="1000">{{ $p->descripcion }}</textarea>
                            </div>
                            <div class="input-group mb-2">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="precio" value="{{ (float) $p->precio }}" min="0" step="1" required>
                                <span class="input-group-text">/ mes</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="activo" value="0">
                                    <input class="form-check-input" type="checkbox" name="activo" value="1" id="act{{ $p->id }}" @checked($p->activo)>
                                    <label class="form-check-label small" for="act{{ $p->id }}">Activo</label>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                            </div>
                        </form>
                    @endforeach

                    @if ($unipersonal->isEmpty())
                        <form method="POST" action="{{ route('admin.planes.store') }}">
                            @csrf
                            <input type="hidden" name="tipo" value="unipersonal">
                            <input type="text" class="form-control form-control-sm mb-2" name="nombre" value="Docente" required>
                            <input type="number" class="form-control form-control-sm mb-2" name="precio" placeholder="Precio por mes" min="0" required>
                            <button type="submit" class="btn btn-sm btn-primary">Crear</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Institucional --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-1"><i class="bi bi-building me-1"></i>Abono institucional — precio por docente por mes</h2>
                    <p class="small text-muted">Cuantos más docentes contrata la institución, menor el precio de cada uno. Dejá "hasta" vacío en la última escala.</p>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light"><tr><th>Desde</th><th>Hasta</th><th>Precio c/u</th><th>Nombre</th><th>Activo</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($escalas as $e)
                                    <tr>
                                        <td><input form="f{{ $e->id }}" type="number" class="form-control form-control-sm" name="docentes_desde" value="{{ $e->docentes_desde }}" min="1" required style="width: 80px"></td>
                                        <td><input form="f{{ $e->id }}" type="number" class="form-control form-control-sm" name="docentes_hasta" value="{{ $e->docentes_hasta }}" min="1" placeholder="∞" style="width: 80px"></td>
                                        <td><input form="f{{ $e->id }}" type="number" class="form-control form-control-sm" name="precio" value="{{ (float) $e->precio }}" min="0" required style="width: 110px"></td>
                                        <td><input form="f{{ $e->id }}" type="text" class="form-control form-control-sm" name="nombre" value="{{ $e->nombre }}" required maxlength="100"></td>
                                        <td>
                                            <input form="f{{ $e->id }}" type="hidden" name="activo" value="0">
                                            <input form="f{{ $e->id }}" class="form-check-input" type="checkbox" name="activo" value="1" @checked($e->activo)>
                                        </td>
                                        <td class="text-nowrap">
                                            <form method="POST" action="{{ route('admin.planes.update', $e) }}" id="f{{ $e->id }}" class="d-inline">@csrf @method('PUT')</form>
                                            <button form="f{{ $e->id }}" type="submit" class="btn btn-sm btn-primary" title="Guardar"><i class="bi bi-check-lg"></i></button>
                                            <form method="POST" action="{{ route('admin.planes.destroy', $e) }}" class="d-inline" data-confirmar="¿Eliminar esta escala?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('admin.planes.store') }}" class="row g-2 align-items-end border-top pt-3">
                        @csrf
                        <input type="hidden" name="tipo" value="institucional">
                        <div class="col-3"><label class="form-label small mb-1">Desde</label><input type="number" class="form-control form-control-sm" name="docentes_desde" min="1" required></div>
                        <div class="col-3"><label class="form-label small mb-1">Hasta</label><input type="number" class="form-control form-control-sm" name="docentes_hasta" min="1" placeholder="∞"></div>
                        <div class="col-3"><label class="form-label small mb-1">Precio c/u</label><input type="number" class="form-control form-control-sm" name="precio" min="0" required></div>
                        <div class="col-3"><input type="hidden" name="nombre" value="Escala"><button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-plus-lg"></i> Agregar</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
