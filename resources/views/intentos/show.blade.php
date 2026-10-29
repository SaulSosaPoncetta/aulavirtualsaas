@extends('layouts.app')

@section('titulo', 'Resultado — ' . $evaluacion->titulo)

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('evaluaciones.show', $evaluacion) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $evaluacion->titulo }}</a>
            <h1 class="mt-1">
                @if ($esDocente)
                    <i class="bi bi-person me-2 text-primary"></i>{{ $intento->alumno->nombre_completo }}
                @else
                    <i class="bi bi-clipboard-data me-2 text-primary"></i>Mi resultado
                @endif
            </h1>
            <div class="small text-muted mt-1">
                Intento {{ $intento->numero }} · {{ $intento->iniciado_at->format('d/m/Y H:i') }}
                @if ($intento->finalizado_at) → {{ $intento->finalizado_at->format('H:i') }} @endif
                <span class="badge bg-{{ $intento->estado_color }} ms-1">{{ $intento->estado_label }}</span>
            </div>
        </div>

        <div class="text-end">
            @if ($intento->nota_texto !== null)
                <div class="display-6 fw-bold text-success">{{ $intento->nota_texto }}<span class="fs-5 text-muted">/{{ $evaluacion->puntaje_maximo_texto }}</span></div>
                <div class="small text-muted">{{ $intento->puntaje_texto }} de {{ \App\Models\Evaluacion::numero($totalPuntos) }} puntos</div>
            @elseif ($intento->estado === 'pendiente_revision')
                <div class="fw-semibold text-warning"><i class="bi bi-hourglass-split me-1"></i>Falta corregir respuestas escritas</div>
            @endif
        </div>
    </div>

    @if (! $verDetalle)
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-check2-circle display-4 text-success"></i>
                <p class="mt-3 mb-0">
                    Tu evaluación se entregó correctamente.
                    @if ($evaluacion->mostrar_resultados === 'al_cerrar')
                        Las respuestas correctas se van a ver cuando cierre la evaluación{{ $evaluacion->fecha_cierre ? ', el ' . $evaluacion->fecha_cierre->format('d/m/Y H:i') : '' }}.
                    @endif
                </p>
            </div>
        </div>
    @else
        @if ($esDocente)
            <form method="POST" action="{{ route('intentos.calificar', $intento) }}">
                @csrf @method('PATCH')
        @endif

        @foreach ($preguntas as $p)
            @php($r = $respuestas[$p->id] ?? null)
            <div class="card mb-3 border-start border-4 {{ $r?->correcta === true ? 'border-success' : ($r?->correcta === false ? 'border-danger' : 'border-warning') }}">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between gap-3 mb-3">
                        <div class="fw-semibold" style="white-space: pre-line"><span class="text-primary me-1">{{ $loop->iteration }}.</span>{{ $p->enunciado }}</div>
                        <span class="badge align-self-start {{ $r?->puntos === null ? 'bg-warning text-dark' : 'bg-light text-dark' }}">
                            {{ $r?->puntos !== null ? \App\Models\Evaluacion::numero($r->puntos) : '?' }} / {{ $p->puntos_texto }} pts
                        </span>
                    </div>

                    @if ($p->tipo === 'texto')
                        <div class="bg-light rounded p-3 mb-2">
                            @if ($r?->texto)
                                @include('partials._texto', ['texto' => $r->texto])
                            @else
                                <span class="text-muted fst-italic">Sin responder</span>
                            @endif
                        </div>
                    @else
                        @foreach ($p->opciones as $o)
                            @php($elegida = $r && $r->eligio((int) $o->id))
                            <div class="d-flex align-items-center gap-2 py-1
                                        {{ $o->es_correcta ? 'text-success fw-semibold' : ($elegida ? 'text-danger' : '') }}">
                                <i class="bi {{ $elegida ? ($p->tipo === 'multiple' ? 'bi-check-square-fill' : 'bi-record-circle-fill') : ($p->tipo === 'multiple' ? 'bi-square' : 'bi-circle') }}"></i>
                                <span>{{ $o->texto }}</span>
                                @if ($o->es_correcta)<i class="bi bi-check-lg"></i>@endif
                                @if ($elegida && ! $o->es_correcta)<i class="bi bi-x-lg"></i>@endif
                            </div>
                        @endforeach
                        @if (! $r || empty($r->opciones_ids))
                            <div class="small text-muted fst-italic mt-1">Sin responder</div>
                        @endif
                    @endif

                    @if ($p->explicacion)
                        <div class="small text-muted mt-2"><i class="bi bi-lightbulb me-1"></i>{{ $p->explicacion }}</div>
                    @endif

                    @if ($esDocente && $r && $intento->estado !== 'en_curso')
                        <div class="row g-2 mt-3 pt-3 border-top">
                            <div class="col-sm-3">
                                <label class="form-label small mb-1" for="pts{{ $r->id }}">Puntos (0 a {{ $p->puntos_texto }})</label>
                                <input type="text" inputmode="decimal" class="form-control form-control-sm @error('puntos.' . $r->id) is-invalid @enderror"
                                       id="pts{{ $r->id }}" name="puntos[{{ $r->id }}]"
                                       value="{{ old('puntos.' . $r->id, $r->puntos !== null ? \App\Models\Evaluacion::numero($r->puntos) : '') }}"
                                       @if ($p->tipo === 'texto' && $r->puntos === null) autofocus @endif>
                                @error('puntos.' . $r->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-9">
                                <label class="form-label small mb-1" for="com{{ $r->id }}">Comentario para el alumno</label>
                                <input type="text" class="form-control form-control-sm" id="com{{ $r->id }}" name="comentario[{{ $r->id }}]"
                                       value="{{ old('comentario.' . $r->id, $r->comentario) }}" maxlength="5000">
                            </div>
                        </div>
                    @elseif ($r?->comentario)
                        <div class="alert alert-info small mt-3 mb-0"><i class="bi bi-chat-left-text me-1"></i>{{ $r->comentario }}</div>
                    @endif
                </div>
            </div>
        @endforeach

        @if ($esDocente)
                @if ($intento->estado !== 'en_curso')
                    <div class="card sticky-bottom">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Las preguntas de opción se corrigen solas; podés ajustar los puntos si hace falta.</span>
                            <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i> Guardar corrección</button>
                        </div>
                    </div>
                @endif
            </form>
        @endif
    @endif
@endsection
