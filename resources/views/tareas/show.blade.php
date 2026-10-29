@extends('layouts.app')

@section('titulo', $tarea->titulo)

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('aulas.show', $aula) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $aula->nombre }}</a>
            <h1 class="mt-1"><i class="bi bi-clipboard-check text-warning me-2"></i>{{ $tarea->titulo }}</h1>
            <div class="small text-muted mt-1">
                @if ($tarea->unidad)<i class="bi bi-folder2-open me-1"></i>{{ $tarea->unidad->titulo }} · @endif
                Nota máxima: {{ $tarea->puntaje_maximo_texto }}
                @if ($tarea->autor) · {{ $tarea->autor->nombre_completo }} @endif
            </div>
        </div>

        @if ($puedeEditar)
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if ($tarea->estado === 'oculta')
                    <span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>Oculta para alumnos</span>
                @elseif ($tarea->estado === 'programada')
                    <span class="badge badge-pendiente"><i class="bi bi-clock me-1"></i>Se publica el {{ $tarea->fecha_publicacion->format('d/m/Y H:i') }}</span>
                @endif
                <a href="{{ route('tareas.edit', $tarea) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> Editar</a>
                <form method="POST" action="{{ route('tareas.destroy', $tarea) }}"
                      data-confirmar="¿Eliminar esta tarea? También se borran todas las entregas de los alumnos.">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        @endif
    </div>

    {{-- Fecha límite --}}
    <div class="alert {{ $tarea->estaVencida() ? 'alert-danger' : 'alert-light border' }} d-flex align-items-center gap-2">
        <i class="bi bi-alarm fs-5"></i>
        <div>
            @if ($tarea->fecha_limite)
                <strong>{{ $tarea->estaVencida() ? 'Venció' : 'Vence' }} el {{ $tarea->fecha_limite->format('d/m/Y') }} a las {{ $tarea->fecha_limite->format('H:i') }}</strong>
                @if ($tarea->estaVencida())
                    · {{ $tarea->acepta_tardias ? 'Se aceptan entregas fuera de término.' : 'Ya no se aceptan entregas.' }}
                @endif
            @else
                <strong>Sin fecha límite</strong>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- Consigna --}}
        <div class="col-lg-{{ $puedeEditar ? '5' : '7' }}">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Consigna</h2>
                    @if ($tarea->consigna)
                        <div class="contenido-clase">@include('partials._texto', ['texto' => $tarea->consigna])</div>
                    @else
                        <p class="text-muted mb-0">Sin consigna escrita{{ $tarea->archivos->isNotEmpty() ? ': revisá los archivos adjuntos.' : '.' }}</p>
                    @endif
                </div>
            </div>

            @if ($tarea->archivos->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-2"><i class="bi bi-paperclip me-1"></i>Archivos de la consigna</h2>
                        @include('partials._archivos', ['archivos' => $tarea->archivos])
                    </div>
                </div>
            @endif
        </div>

        @if ($puedeEditar)
            {{-- ===================== Vista docente ===================== --}}
            <div class="col-lg-7">
                <div class="row g-2 mb-3 text-center">
                    <div class="col-3"><div class="card"><div class="card-body py-2"><div class="h4 mb-0">{{ $resumen['alumnos'] }}</div><div class="small text-muted">Alumnos</div></div></div></div>
                    <div class="col-3"><div class="card"><div class="card-body py-2"><div class="h4 mb-0">{{ $resumen['entregadas'] }}</div><div class="small text-muted">Entregaron</div></div></div></div>
                    <div class="col-3"><div class="card"><div class="card-body py-2"><div class="h4 mb-0 text-primary">{{ $resumen['corregir'] }}</div><div class="small text-muted">Por corregir</div></div></div></div>
                    <div class="col-3"><div class="card"><div class="card-body py-2"><div class="h4 mb-0 text-success">{{ $resumen['corregidas'] }}</div><div class="small text-muted">Corregidas</div></div></div></div>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Alumno</th>
                                    <th>Estado</th>
                                    <th class="text-center">Nota</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($alumnos as $al)
                                    @php($en = $entregas[$al->id] ?? null)
                                    <tr>
                                        <td>{{ $al->nombre_completo }}</td>
                                        <td>
                                            @if ($en)
                                                <span class="badge bg-{{ $en->estado_color }}">{{ $en->estado_label }}</span>
                                                @if ($en->esTardia())<span class="badge bg-danger-subtle text-danger-emphasis">Tarde</span>@endif
                                                <div class="small text-muted">{{ $en->entregada_at?->format('d/m H:i') }}</div>
                                            @else
                                                <span class="badge bg-light text-muted">Sin entregar</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-semibold">{{ $en?->nota_texto ?? '—' }}</td>
                                        <td class="text-end">
                                            @if ($en)
                                                <a href="{{ route('entregas.show', $en) }}" class="btn btn-sm {{ $en->estado === 'entregada' ? 'btn-primary' : 'btn-outline-primary' }}">
                                                    {{ $en->estado === 'entregada' ? 'Corregir' : 'Ver' }}
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">El aula todavía no tiene alumnos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            {{-- ===================== Vista alumno ===================== --}}
            <div class="col-lg-5">
                @if ($entrega && $entrega->estado === 'corregida')
                    <div class="card border-success mb-4">
                        <div class="card-body">
                            <h2 class="h6 fw-bold text-success mb-2"><i class="bi bi-patch-check me-1"></i>Corregida</h2>
                            @if ($entrega->nota_texto !== null)
                                <div class="display-6 fw-bold mb-2">{{ $entrega->nota_texto }}<span class="fs-5 text-muted">/{{ $tarea->puntaje_maximo_texto }}</span></div>
                            @endif
                            @if ($entrega->devolucion)
                                <div class="small fw-semibold mt-2">Devolución del docente</div>
                                <div class="bg-light rounded p-2 small">@include('partials._texto', ['texto' => $entrega->devolucion])</div>
                            @endif
                        </div>
                    </div>
                @elseif ($entrega && $entrega->estado === 'devuelta')
                    <div class="alert alert-warning">
                        <strong><i class="bi bi-arrow-return-left me-1"></i>Tu docente te devolvió la entrega para rehacer.</strong>
                        @if ($entrega->devolucion)
                            <div class="mt-2 small">@include('partials._texto', ['texto' => $entrega->devolucion])</div>
                        @endif
                    </div>
                @endif

                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-3">
                            <i class="bi bi-upload me-1"></i>Mi entrega
                            @if ($entrega)
                                <span class="badge bg-{{ $entrega->estado_color }} ms-1">{{ $entrega->estado_label }}</span>
                                @if ($entrega->esTardia())<span class="badge bg-danger-subtle text-danger-emphasis">Tarde</span>@endif
                            @endif
                        </h2>

                        @if ($entrega)
                            <p class="small text-muted">Enviada el {{ $entrega->entregada_at?->format('d/m/Y H:i') }}</p>
                        @endif

                        @if (! $esAlumno)
                            <p class="text-muted small mb-0">Solo los alumnos del aula pueden entregar.</p>
                        @elseif ($entrega && $entrega->estado === 'corregida')
                            @if ($entrega->texto)
                                <div class="bg-light rounded p-2 small mb-3">@include('partials._texto', ['texto' => $entrega->texto])</div>
                            @endif
                            @include('partials._archivos', ['archivos' => $entrega->archivos])
                        @elseif (! $tarea->aceptaEntregas())
                            <div class="alert alert-danger small mb-0">La fecha límite pasó y esta tarea ya no acepta entregas.</div>
                            @if ($entrega)
                                <div class="mt-3">@include('partials._archivos', ['archivos' => $entrega->archivos])</div>
                            @endif
                        @else
                            <form method="POST" action="{{ route('entregas.guardar', $tarea) }}" enctype="multipart/form-data"
                                  data-enviando="Enviando...">
                                @csrf

                                <div class="mb-3">
                                    <label for="texto" class="form-label">Respuesta <span class="text-muted small">(opcional si adjuntás archivos)</span></label>
                                    <textarea class="form-control @error('texto') is-invalid @enderror" id="texto" name="texto" rows="6"
                                              maxlength="60000">{{ old('texto', $entrega?->texto) }}</textarea>
                                    @error('texto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                @if ($entrega && $entrega->archivos->isNotEmpty())
                                    <p class="small text-muted mb-1">Archivos enviados:</p>
                                    <ul class="list-group mb-3">
                                        @foreach ($entrega->archivos as $a)
                                            <li class="list-group-item d-flex align-items-center gap-2 py-2">
                                                <i class="bi {{ $a->icono }}"></i>
                                                <a href="{{ route('archivos.descargar', $a) }}" class="flex-grow-1 text-truncate small">{{ $a->nombre }}</a>
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox" name="quitar_archivos[]" value="{{ $a->id }}" id="q{{ $a->id }}">
                                                    <label class="form-check-label small text-danger" for="q{{ $a->id }}">Quitar</label>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <div class="mb-4">
                                    <label for="archivos" class="form-label">Adjuntar archivos</label>
                                    <input type="file" class="form-control @error('archivos.*') is-invalid @enderror @error('archivos') is-invalid @enderror"
                                           id="archivos" name="archivos[]" multiple>
                                    @error('archivos.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @error('archivos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">Hasta 5 archivos de 20 MB cada uno.</div>
                                </div>

                                @if ($tarea->estaVencida())
                                    <div class="alert alert-warning small">La fecha límite ya pasó: tu entrega va a quedar marcada como <strong>tarde</strong>.</div>
                                @endif

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-send me-1"></i> {{ $entrega ? 'Volver a enviar' : 'Entregar' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
