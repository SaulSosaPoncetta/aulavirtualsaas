{{-- Datos para transferir + forma de pago + comprobante --}}
@php($t = $transferencia)
<div class="border rounded p-3 mb-3">
    <div class="fw-semibold mb-2"><i class="bi bi-bank me-1"></i>Datos para transferir</div>
    <dl class="row small mb-0">
        @if ($t['titular'])<dt class="col-4">Titular</dt><dd class="col-8">{{ $t['titular'] }}</dd>@endif
        @if ($t['cuit'])<dt class="col-4">CUIT</dt><dd class="col-8">{{ $t['cuit'] }}</dd>@endif
        @if ($t['banco'])<dt class="col-4">Banco</dt><dd class="col-8">{{ $t['banco'] }}</dd>@endif
        @if ($t['cbu'])<dt class="col-4">CBU / CVU</dt><dd class="col-8 font-monospace">{{ $t['cbu'] }}</dd>@endif
        @if ($t['alias'])<dt class="col-4">Alias</dt><dd class="col-8 font-monospace">{{ $t['alias'] }}</dd>@endif
        @if (! $t['cbu'] && ! $t['alias'])
            <dd class="col-12 text-muted mb-0">Consultá los datos de pago en {{ config('abonos.contacto') }}.</dd>
        @endif
    </dl>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-5">
        <label for="metodo" class="form-label">Forma de pago</label>
        <select class="form-select" id="metodo" name="metodo">
            <option value="transferencia" @selected(old('metodo') === 'transferencia')>Transferencia</option>
            <option value="efectivo" @selected(old('metodo') === 'efectivo')>Efectivo</option>
            <option value="otro" @selected(old('metodo') === 'otro')>Otro</option>
        </select>
    </div>
    <div class="col-sm-7">
        <label for="comprobante" class="form-label">Comprobante <span class="text-muted small">(opcional)</span></label>
        <input type="file" class="form-control @error('comprobante') is-invalid @enderror" id="comprobante" name="comprobante" accept=".pdf,image/*">
        @error('comprobante')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mb-3">
    <label for="observaciones" class="form-label">Observaciones <span class="text-muted small">(opcional)</span></label>
    <input type="text" class="form-control" id="observaciones" name="observaciones" maxlength="1000"
           value="{{ old('observaciones') }}" placeholder="Ej: transferí desde la cuenta de ...">
</div>
