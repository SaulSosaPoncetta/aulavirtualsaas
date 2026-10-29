@extends('layouts.app')

@section('titulo', 'Preguntas — ' . $evaluacion->titulo)

@section('contenido')
    @php($total = $evaluacion->preguntas->sum(fn ($p) => (float) $p->puntos))

    <div class="page-header">
        <div>
            <a href="{{ route('evaluaciones.show', $evaluacion) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $evaluacion->titulo }}</a>
            <h1 class="mt-1"><i class="bi bi-list-check text-success me-2"></i>Preguntas</h1>
            <div class="small text-muted mt-1">
                {{ $evaluacion->preguntas->count() }} preguntas · {{ \App\Models\Evaluacion::numero($total) }} puntos en total
                · se llevan a nota sobre {{ $evaluacion->puntaje_maximo_texto }}
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPregunta" data-pregunta="">
                <i class="bi bi-plus-lg me-1"></i> Agregar pregunta
            </button>
            <a href="{{ route('evaluaciones.show', $evaluacion) }}" class="btn btn-outline-success"><i class="bi bi-check2 me-1"></i> Listo</a>
        </div>
    </div>

    @if ($hayIntentos)
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Algunos alumnos ya rindieron. Si cambiás preguntas, sus intentos corregidos no se recalculan.
        </div>
    @endif

    @error('opciones')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @error('vf')<div class="alert alert-danger">{{ $message }}</div>@enderror

    @forelse ($evaluacion->preguntas as $p)
        @php
            $datos = [
                'accion'      => route('preguntas.update', $p),
                'tipo'        => $p->tipo,
                'enunciado'   => $p->enunciado,
                'puntos'      => $p->puntos_texto,
                'explicacion' => $p->explicacion,
                'opciones'    => $p->opciones->map(fn ($o) => ['texto' => $o->texto, 'correcta' => $o->es_correcta])->values(),
                'vf'          => $p->tipo === 'verdadero_falso' ? ($p->opciones->firstWhere('es_correcta', true)?->texto === 'Verdadero' ? 'verdadero' : 'falso') : null,
            ];
        @endphp
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex gap-3">
                    <div class="fw-bold text-primary fs-5">{{ $loop->iteration }}.</div>
                    <div class="flex-grow-1">
                        <div class="mb-2" style="white-space: pre-line">{{ $p->enunciado }}</div>

                        @if ($p->tipo === 'texto')
                            <div class="border rounded p-2 small text-muted bg-light">Respuesta escrita (se corrige a mano)</div>
                        @else
                            <ul class="list-unstyled mb-0">
                                @foreach ($p->opciones as $o)
                                    <li class="{{ $o->es_correcta ? 'text-success fw-semibold' : '' }}">
                                        <i class="bi {{ $o->es_correcta ? 'bi-check-circle-fill' : ($p->tipo === 'multiple' ? 'bi-square' : 'bi-circle') }} me-1"></i>{{ $o->texto }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($p->explicacion)
                            <div class="small text-muted mt-2"><i class="bi bi-lightbulb me-1"></i>{{ $p->explicacion }}</div>
                        @endif
                    </div>

                    <div class="text-end">
                        <span class="badge bg-light text-dark d-block mb-1">{{ $p->tipo_label }}</span>
                        <span class="badge bg-primary d-block mb-2">{{ $p->puntos_texto }} pts</span>
                        <div class="btn-group btn-group-sm">
                            <form method="POST" action="{{ route('preguntas.mover', [$p, 'arriba']) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-outline-secondary" @disabled($loop->first) title="Subir"><i class="bi bi-arrow-up"></i></button>
                            </form>
                            <form method="POST" action="{{ route('preguntas.mover', [$p, 'abajo']) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-outline-secondary" @disabled($loop->last) title="Bajar"><i class="bi bi-arrow-down"></i></button>
                            </form>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPregunta"
                                    data-pregunta="{{ json_encode($datos) }}" title="Editar"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="{{ route('preguntas.destroy', $p) }}" data-confirmar="¿Eliminar esta pregunta?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-question-square display-5"></i>
                <p class="mt-3 mb-3">Todavía no cargaste preguntas. Los alumnos no pueden rendir hasta que haya al menos una.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPregunta" data-pregunta="">
                    <i class="bi bi-plus-lg me-1"></i> Agregar la primera
                </button>
            </div>
        </div>
    @endforelse

    {{-- Modal: crear / editar pregunta --}}
    <div class="modal fade" id="modalPregunta" tabindex="-1" aria-labelledby="modalPreguntaTitulo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form method="POST" class="modal-content" id="formPregunta"
                  data-accion-nueva="{{ route('preguntas.store', $evaluacion) }}">
                @csrf
                <input type="hidden" name="_method" id="preguntaMetodo" value="POST">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalPreguntaTitulo">Pregunta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="preguntaTipo" class="form-label">Tipo</label>
                            <select class="form-select" id="preguntaTipo" name="tipo">
                                @foreach (\App\Models\Pregunta::TIPOS as $valor => $texto)
                                    <option value="{{ $valor }}">{{ $texto }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="preguntaPuntos" class="form-label">Puntos</label>
                            <input type="text" inputmode="decimal" class="form-control" id="preguntaPuntos" name="puntos" value="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="preguntaEnunciado" class="form-label">Pregunta / afirmación</label>
                        <textarea class="form-control" id="preguntaEnunciado" name="enunciado" rows="3" required maxlength="5000"></textarea>
                    </div>

                    {{-- Opciones (única / múltiple) --}}
                    <div id="bloqueOpciones" class="mb-3">
                        <label class="form-label">Opciones <span class="small text-muted" id="ayudaCorrectas">— marcá la correcta</span></label>
                        <div id="listaOpciones"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarOpcion">
                            <i class="bi bi-plus-lg me-1"></i> Otra opción
                        </button>
                    </div>

                    {{-- Verdadero / Falso --}}
                    <div id="bloqueVF" class="mb-3 d-none">
                        <label class="form-label d-block">La afirmación es</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="vf" id="vfVerdadero" value="verdadero">
                            <label class="btn btn-outline-success" for="vfVerdadero">Verdadera</label>
                            <input type="radio" class="btn-check" name="vf" id="vfFalso" value="falso">
                            <label class="btn btn-outline-danger" for="vfFalso">Falsa</label>
                        </div>
                    </div>

                    <div id="bloqueTexto" class="alert alert-light border small d-none">
                        El alumno escribe su respuesta y vos la corregís a mano, con los puntos que correspondan.
                    </div>

                    <div>
                        <label for="preguntaExplicacion" class="form-label">Explicación <span class="text-muted small">(opcional, la ve el alumno con los resultados)</span></label>
                        <textarea class="form-control" id="preguntaExplicacion" name="explicacion" rows="2" maxlength="5000"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar pregunta</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form     = document.getElementById('formPregunta');
    const tipo     = document.getElementById('preguntaTipo');
    const lista    = document.getElementById('listaOpciones');
    let contador   = 0;

    const filaOpcion = (texto = '', correcta = false) => {
        const i = contador++;
        const fila = document.createElement('div');
        fila.className = 'input-group mb-2 fila-opcion';
        fila.innerHTML =
            '<div class="input-group-text"><input class="form-check-input mt-0 marca-correcta" type="checkbox" name="opciones[' + i + '][correcta]" value="1" title="Correcta"></div>' +
            '<input type="text" class="form-control" name="opciones[' + i + '][texto]" maxlength="500" placeholder="Opción">' +
            '<button type="button" class="btn btn-outline-danger quitar-opcion" title="Quitar"><i class="bi bi-x-lg"></i></button>';
        fila.querySelector('input[type=text]').value = texto;
        fila.querySelector('.marca-correcta').checked = !!correcta;
        lista.appendChild(fila);
    };

    const actualizarTipo = () => {
        const t = tipo.value;
        document.getElementById('bloqueOpciones').classList.toggle('d-none', !['unica', 'multiple'].includes(t));
        document.getElementById('bloqueVF').classList.toggle('d-none', t !== 'verdadero_falso');
        document.getElementById('bloqueTexto').classList.toggle('d-none', t !== 'texto');
        document.getElementById('ayudaCorrectas').textContent = t === 'multiple' ? '— marcá todas las correctas' : '— marcá la correcta';
    };

    tipo.addEventListener('change', actualizarTipo);

    // En opción única, marcar una desmarca las demás
    lista.addEventListener('change', (e) => {
        if (e.target.classList.contains('marca-correcta') && e.target.checked && tipo.value === 'unica') {
            lista.querySelectorAll('.marca-correcta').forEach(c => { if (c !== e.target) c.checked = false; });
        }
    });

    lista.addEventListener('click', (e) => {
        const btn = e.target.closest('.quitar-opcion');
        if (btn && lista.querySelectorAll('.fila-opcion').length > 2) btn.closest('.fila-opcion').remove();
    });

    document.getElementById('btnAgregarOpcion').addEventListener('click', () => {
        if (lista.querySelectorAll('.fila-opcion').length < 10) filaOpcion();
    });

    document.getElementById('modalPregunta').addEventListener('show.bs.modal', (e) => {
        const crudo = e.relatedTarget?.dataset.pregunta;
        const p = crudo ? JSON.parse(crudo) : null;

        form.action = p ? p.accion : form.dataset.accionNueva;
        document.getElementById('preguntaMetodo').value = p ? 'PUT' : 'POST';
        document.getElementById('modalPreguntaTitulo').textContent = p ? 'Editar pregunta' : 'Nueva pregunta';

        tipo.value = p ? p.tipo : 'unica';
        document.getElementById('preguntaPuntos').value = p ? p.puntos : '1';
        document.getElementById('preguntaEnunciado').value = p ? p.enunciado : '';
        document.getElementById('preguntaExplicacion').value = p && p.explicacion ? p.explicacion : '';
        document.getElementById('vfVerdadero').checked = p && p.vf === 'verdadero';
        document.getElementById('vfFalso').checked = p && p.vf === 'falso';

        lista.innerHTML = '';
        contador = 0;
        if (p && ['unica', 'multiple'].includes(p.tipo) && p.opciones.length) {
            p.opciones.forEach(o => filaOpcion(o.texto, o.correcta));
        } else {
            for (let k = 0; k < 4; k++) filaOpcion();
        }

        actualizarTipo();
    });

    document.getElementById('modalPregunta').addEventListener('shown.bs.modal', () => {
        document.getElementById('preguntaEnunciado').focus();
    });
});
</script>
@endpush
