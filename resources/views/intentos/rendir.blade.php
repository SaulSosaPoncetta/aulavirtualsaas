@extends('layouts.app')

@section('titulo', $evaluacion->titulo)

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-0">{{ $evaluacion->titulo }}</h1>
            <div class="small text-muted">Intento {{ $intento->numero }} · {{ $preguntas->count() }} preguntas</div>
        </div>
        @if ($restantes !== null)
            <div class="reloj-evaluacion badge bg-dark fs-5 px-3 py-2" id="reloj" data-segundos="{{ $restantes }}">
                <i class="bi bi-stopwatch me-1"></i><span id="relojTexto">--:--</span>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('intentos.finalizar', $intento) }}" id="formRendir">
        @csrf

        @foreach ($preguntas as $p)
            <div class="card mb-3">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between gap-3 mb-3">
                        <div class="fw-semibold" style="white-space: pre-line"><span class="text-primary me-1">{{ $loop->iteration }}.</span>{{ $p->enunciado }}</div>
                        <span class="badge bg-light text-dark align-self-start">{{ $p->puntos_texto }} pts</span>
                    </div>

                    @if ($p->tipo === 'texto')
                        <textarea class="form-control" name="respuestas[{{ $p->id }}]" rows="4" maxlength="20000"
                                  placeholder="Escribí tu respuesta">{{ old('respuestas.' . $p->id) }}</textarea>
                    @else
                        @if ($p->tipo === 'multiple')
                            <div class="small text-muted mb-2">Puede haber más de una correcta: marcá todas.</div>
                        @endif
                        @foreach ($p->opciones as $o)
                            <div class="form-check opcion-rendir mb-2">
                                @if ($p->tipo === 'multiple')
                                    <input class="form-check-input" type="checkbox" name="respuestas[{{ $p->id }}][]" value="{{ $o->id }}" id="o{{ $o->id }}">
                                @else
                                    <input class="form-check-input" type="radio" name="respuestas[{{ $p->id }}]" value="{{ $o->id }}" id="o{{ $o->id }}">
                                @endif
                                <label class="form-check-label w-100" for="o{{ $o->id }}">{{ $o->texto }}</label>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endforeach

        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="small text-muted" id="contadorRespondidas"></span>
                <button type="button" class="btn btn-success btn-lg" id="btnEntregar">
                    <i class="bi bi-send-check me-1"></i> Entregar evaluación
                </button>
            </div>
        </div>
    </form>

    {{-- Confirmación de entrega --}}
    <div class="modal fade" id="modalEntregar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">¿Entregar la evaluación?</h5></div>
                <div class="modal-body"><p class="mb-0" id="textoEntregar"></p></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Seguir revisando</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarEntrega">Entregar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form   = document.getElementById('formRendir');
    const total  = {{ $preguntas->count() }};
    let enviando = false;

    const enviar = () => {
        if (enviando) return;
        enviando = true;
        window.onbeforeunload = null;
        form.submit();
    };

    // Preguntas respondidas
    const contar = () => {
        const ids = new Set();
        form.querySelectorAll('input:checked').forEach(i => ids.add(i.name.replace('[]', '')));
        form.querySelectorAll('textarea').forEach(t => { if (t.value.trim() !== '') ids.add(t.name); });
        return ids.size;
    };
    const mostrarContador = () => {
        document.getElementById('contadorRespondidas').textContent = 'Respondiste ' + contar() + ' de ' + total;
    };
    form.addEventListener('input', mostrarContador);
    form.addEventListener('change', mostrarContador);
    mostrarContador();

    // Confirmar entrega
    const modal = new bootstrap.Modal(document.getElementById('modalEntregar'));
    document.getElementById('btnEntregar').addEventListener('click', () => {
        const faltan = total - contar();
        document.getElementById('textoEntregar').textContent = faltan > 0
            ? 'Te quedan ' + faltan + ' preguntas sin responder. Una vez entregada no podés cambiar las respuestas.'
            : 'Respondiste todas las preguntas. Una vez entregada no podés cambiar las respuestas.';
        modal.show();
    });
    document.getElementById('btnConfirmarEntrega').addEventListener('click', enviar);

    // Aviso si intenta salir de la página sin entregar
    window.onbeforeunload = () => true;

    // Reloj: al llegar a 0 se entrega sola
    const reloj = document.getElementById('reloj');
    if (reloj) {
        const fin = Date.now() + parseInt(reloj.dataset.segundos, 10) * 1000;
        const texto = document.getElementById('relojTexto');
        const tic = () => {
            const s = Math.max(0, Math.round((fin - Date.now()) / 1000));
            const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), seg = s % 60;
            texto.textContent = (h > 0 ? h + ':' + String(m).padStart(2, '0') : m) + ':' + String(seg).padStart(2, '0');
            reloj.classList.toggle('bg-danger', s <= 60);
            reloj.classList.toggle('bg-dark', s > 60);
            if (s === 0) { enviar(); return; }
            setTimeout(tic, 500);
        };
        tic();
    }
});
</script>
@endpush
