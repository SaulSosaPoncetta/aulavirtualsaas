@csrf
@php
    $escalasForm = old('escalas', $plan->exists
        ? $plan->escalas->map(fn ($e) => ['desde' => $e->desde, 'hasta' => $e->hasta, 'precio' => $e->precio])->all()
        : []);
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Sistema</label>
        <select name="id_sistema" class="form-select @error('id_sistema') is-invalid @enderror" required>
            <option value="">Elegí un sistema…</option>
            @foreach($sistemas as $s)
                <option value="{{ $s->id_sistema }}" @selected(old('id_sistema', $plan->id_sistema) == $s->id_sistema)>{{ $s->nombre }}</option>
            @endforeach
        </select>
        @error('id_sistema')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Nombre del plan</label>
        <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
               value="{{ old('nombre', $plan->nombre) }}" placeholder="Ej: Docente individual" required>
        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Código</label>
        <input type="text" name="codigo" class="form-control @error('codigo') is-invalid @enderror"
               value="{{ old('codigo', $plan->codigo) }}" placeholder="Ej: unipersonal" required>
        <div class="form-text">Es el identificador que usan los sistemas en el enlace de pago. Sin espacios.</div>
        @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Descripción (opcional)</label>
        <input type="text" name="descripcion" class="form-control" value="{{ old('descripcion', $plan->descripcion) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Tipo de cobro</label>
        <select name="tipo" id="tipo" class="form-select">
            <option value="recurrente" @selected(old('tipo', $plan->tipo) === 'recurrente')>Débito automático (se renueva solo)</option>
            <option value="unico" @selected(old('tipo', $plan->tipo) === 'unico')>Pago adelantado (el cliente renueva)</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Cada pago cubre (meses)</label>
        <input type="number" name="duracion_meses" min="1" max="120"
               class="form-control @error('duracion_meses') is-invalid @enderror"
               value="{{ old('duracion_meses', $plan->duracion_meses) }}">
        <div class="form-text">1 = mensual, 12 = anual. Vacío = sin vencimiento.</div>
        @error('duracion_meses')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Precio</label>
        <input type="number" step="0.01" min="0" name="precio" class="form-control @error('precio') is-invalid @enderror"
               value="{{ old('precio', $plan->precio) }}" required>
        <div class="form-text" id="ayuda-precio">Precio de cada pago.</div>
        @error('precio')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Moneda</label>
        <input type="text" name="moneda" maxlength="3" class="form-control" value="{{ old('moneda', $plan->moneda ?? 'ARS') }}" required>
    </div>

    <div class="col-md-6" id="grupo-meses">
        <label class="form-label">Meses que el cliente puede pagar de una vez (opcional)</label>
        <input type="text" name="meses_permitidos" class="form-control @error('meses_permitidos') is-invalid @enderror"
               value="{{ old('meses_permitidos', $plan->meses_permitidos) }}" placeholder="1,3,6,12">
        <div class="form-text">Si lo completás, el precio pasa a ser <strong>por mes</strong> y el cliente elige cuántos meses paga. Solo para "pago adelantado".</div>
        @error('meses_permitidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="cobro_por_unidad" name="cobro_por_unidad" value="1"
                   @checked(old('cobro_por_unidad', $plan->cobro_por_unidad))>
            <label class="form-check-label" for="cobro_por_unidad">
                El precio es por unidad (ej: por docente). El total es precio × cantidad.
            </label>
        </div>
    </div>

    <div class="col-md-4 campo-unidad">
        <label class="form-label">Nombre de la unidad</label>
        <input type="text" name="unidad_nombre" class="form-control" value="{{ old('unidad_nombre', $plan->unidad_nombre) }}" placeholder="docentes">
    </div>
    <div class="col-md-4 campo-unidad">
        <label class="form-label">Mínimo</label>
        <input type="number" min="1" name="unidad_minima" class="form-control" value="{{ old('unidad_minima', $plan->unidad_minima ?? 1) }}">
    </div>
    <div class="col-md-4 campo-unidad">
        <label class="form-label">Máximo (opcional)</label>
        <input type="number" min="1" name="unidad_maxima" class="form-control @error('unidad_maxima') is-invalid @enderror"
               value="{{ old('unidad_maxima', $plan->unidad_maxima) }}">
        @error('unidad_maxima')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 campo-unidad">
        <label class="form-label">Escalas de precio por cantidad (opcional)</label>
        <div class="form-text mb-2">
            Si la cantidad total cae en una escala, <strong>todas</strong> las unidades se cobran al precio de esa escala
            (por mes si cargaste meses permitidos). Si no cargás escalas, vale el precio de arriba.
        </div>
        @if($errors->has('escalas.*') || $errors->has('escalas'))
            <div class="alert alert-danger py-2">{{ $errors->first('escalas.0.desde') ?: ($errors->first('escalas.0.hasta') ?: ($errors->first('escalas.0.precio') ?: $errors->first('escalas'))) }}</div>
        @endif
        <table class="table table-sm align-middle" style="max-width: 560px;">
            <thead class="table-light"><tr><th>Desde</th><th>Hasta (vacío = sin tope)</th><th>Precio por unidad</th><th></th></tr></thead>
            <tbody id="filas-escalas">
                @foreach($escalasForm as $i => $e)
                <tr>
                    <td><input type="number" min="1" class="form-control form-control-sm" name="escalas[{{ $i }}][desde]" value="{{ $e['desde'] ?? '' }}"></td>
                    <td><input type="number" min="1" class="form-control form-control-sm" name="escalas[{{ $i }}][hasta]" value="{{ $e['hasta'] ?? '' }}"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="escalas[{{ $i }}][precio]" value="{{ $e['precio'] ?? '' }}"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger quitar-escala"><i class="bi bi-x-lg"></i></button></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="agregar-escala"><i class="bi bi-plus-lg"></i> Agregar escala</button>
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1"
                   @checked(old('activo', $plan->activo ?? true))>
            <label class="form-check-label" for="activo">Plan activo (disponible para contratar)</label>
        </div>
    </div>
</div>

<div class="mt-4">
    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
    <a href="{{ route('planes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
</div>

<script>
    (function () {
        const chk = document.getElementById('cobro_por_unidad');
        const tipo = document.getElementById('tipo');
        const meses = document.getElementById('grupo-meses');
        const ayuda = document.getElementById('ayuda-precio');
        const unidad = document.querySelectorAll('.campo-unidad');
        const filas = document.getElementById('filas-escalas');
        let indice = filas.querySelectorAll('tr').length;

        function actualizar() {
            unidad.forEach(c => c.style.display = chk.checked ? '' : 'none');
            meses.style.display = tipo.value === 'unico' ? '' : 'none';
            const mensual = tipo.value === 'unico' && meses.querySelector('input').value.trim() !== '';
            ayuda.textContent = (mensual ? 'Precio por mes' : 'Precio de cada pago') + (chk.checked ? ', por unidad.' : '.');
        }

        document.getElementById('agregar-escala').addEventListener('click', function () {
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td><input type="number" min="1" class="form-control form-control-sm" name="escalas[' + indice + '][desde]"></td>' +
                '<td><input type="number" min="1" class="form-control form-control-sm" name="escalas[' + indice + '][hasta]"></td>' +
                '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="escalas[' + indice + '][precio]"></td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger quitar-escala"><i class="bi bi-x-lg"></i></button></td>';
            filas.appendChild(tr);
            indice++;
        });

        filas.addEventListener('click', function (e) {
            const boton = e.target.closest('.quitar-escala');
            if (boton) boton.closest('tr').remove();
        });

        chk.addEventListener('change', actualizar);
        tipo.addEventListener('change', actualizar);
        meses.querySelector('input').addEventListener('input', actualizar);
        actualizar();
    })();
</script>
