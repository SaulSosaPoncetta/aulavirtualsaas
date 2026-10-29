@extends('layouts.app')

@section('titulo', 'Entrega de ' . $entrega->alumno->nombre_completo)

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('tareas.show', $tarea) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $tarea->titulo }}</a>
            <h1 class="mt-1"><i class="bi bi-person me-2 text-primary"></i>{{ $entrega->alumno->nombre_completo }}</h1>
            <div class="small text-muted mt-1">
                Entregada el {{ $entrega->entregada_at?->format('d/m/Y H:i') }}
                @if ($entrega->esTardia())<span class="badge bg-danger ms-1">Tarde</span>@endif
                <span class="badge bg-{{ $entrega->estado_color }} ms-1">{{ $entrega->estado_label }}</span>
            </div>
        </div>
        <div class="btn-group">
            <a href="{{ $anterior ? route('entregas.show', $anterior) : '#' }}" class="btn btn-outline-secondary {{ $anterior ? '' : 'disabled' }}">
                <i class="bi bi-chevron-left"></i> Anterior
            </a>
            <a href="{{ $siguiente ? route('entregas.show', $siguiente) : '#' }}" class="btn btn-outline-secondary {{ $siguiente ? '' : 'disabled' }}">
                Siguiente <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Respuesta del alumno</h2>
                    @if ($entrega->texto)
                        <div class="contenido-clase">@include('partials._texto', ['texto' => $entrega->texto])</div>
                    @else
                        <p class="text-muted mb-0">Sin texto: revisá los archivos.</p>
                    @endif
                </div>
            </div>

            @if ($entrega->archivos->isNotEmpty())
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-2"><i class="bi bi-paperclip me-1"></i>Archivos entregados</h2>
                        @include('partials._archivos', ['archivos' => $entrega->archivos])
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-pencil-square me-1"></i>Corrección</h2>

                    <form method="POST" action="{{ route('entregas.corregir', $entrega) }}">
                        @csrf @method('PATCH')

                        <div class="mb-3">
                            <label for="nota" class="form-label">Nota <span class="text-muted small">(sobre {{ $tarea->puntaje_maximo_texto }})</span></label>
                            <input type="text" inputmode="decimal" class="form-control form-control-lg @error('nota') is-invalid @enderror"
                                   id="nota" name="nota" value="{{ old('nota', $entrega->nota_texto) }}" placeholder="Ej: 8 o 7,5">
                            @error('nota')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label for="devolucion" class="form-label">Devolución para el alumno</label>
                            <textarea class="form-control @error('devolucion') is-invalid @enderror" id="devolucion" name="devolucion"
                                      rows="6" maxlength="20000"
                                      placeholder="Comentarios, qué estuvo bien, qué mejorar...">{{ old('devolucion', $entrega->devolucion) }}</textarea>
                            @error('devolucion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="accion" value="corregir" class="btn btn-success">
                                <i class="bi bi-check2-circle me-1"></i> Guardar corrección
                            </button>
                            @if ($siguiente)
                                <button type="submit" name="accion" value="corregir" class="btn btn-outline-success"
                                        onclick="this.form.siguiente.value='{{ $siguiente }}'">
                                    <i class="bi bi-check2-circle me-1"></i> Guardar y seguir con la próxima
                                </button>
                            @endif
                            <button type="submit" name="accion" value="devolver" class="btn btn-outline-warning">
                                <i class="bi bi-arrow-return-left me-1"></i> Devolver para rehacer
                            </button>
                        </div>
                        <input type="hidden" name="siguiente" value="">
                    </form>

                    @if ($entrega->corregida_at)
                        <p class="small text-muted mt-3 mb-0">
                            Última corrección: {{ $entrega->corregida_at->format('d/m/Y H:i') }}
                            @if ($entrega->correctora) por {{ $entrega->correctora->nombre_completo }} @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
