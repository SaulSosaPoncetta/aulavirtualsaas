@extends('layouts.app')

@section('titulo', $evaluacion->titulo)

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('aulas.show', $aula) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $aula->nombre }}</a>
            <h1 class="mt-1"><i class="bi bi-ui-checks text-success me-2"></i>{{ $evaluacion->titulo }}</h1>
        </div>

        @if ($puedeEditar)
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if ($evaluacion->estado === 'oculta')
                    <span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>Oculta para alumnos</span>
                @elseif ($evaluacion->estado === 'programada')
                    <span class="badge badge-pendiente"><i class="bi bi-clock me-1"></i>Abre el {{ $evaluacion->fecha_publicacion->format('d/m/Y H:i') }}</span>
                @endif
                <a href="{{ route('evaluaciones.preguntas', $evaluacion) }}" class="btn btn-primary"><i class="bi bi-list-check me-1"></i> Preguntas</a>
                <a href="{{ route('evaluaciones.edit', $evaluacion) }}" class="btn btn-outline-primary"><i class="bi bi-gear me-1"></i> Configuración</a>
                <form method="POST" action="{{ route('evaluaciones.destroy', $evaluacion) }}"
                      data-confirmar="¿Eliminar esta evaluación? También se borran todos los intentos de los alumnos.">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        @endif
    </div>

    <div class="row g-4">
        {{-- Datos de la evaluación --}}
        <div class="col-lg-{{ $puedeEditar ? '4' : '7' }}">
            <div class="card mb-4">
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><i class="bi bi-question-circle me-2 text-primary"></i>{{ $evaluacion->preguntas_count }} preguntas · nota sobre {{ $evaluacion->puntaje_maximo_texto }}</li>
                        <li class="mb-2"><i class="bi bi-hourglass-split me-2 text-primary"></i>{{ $evaluacion->duracion_minutos ? $evaluacion->duracion_minutos . ' minutos para resolverla' : 'Sin límite de tiempo' }}</li>
                        <li class="mb-2"><i class="bi bi-arrow-repeat me-2 text-primary"></i>{{ $evaluacion->intentos_permitidos }} {{ $evaluacion->intentos_permitidos === 1 ? 'intento' : 'intentos' }}{{ $evaluacion->intentos_permitidos > 1 ? ' (cuenta la mejor nota)' : '' }}</li>
                        <li class="mb-2"><i class="bi bi-calendar-check me-2 text-primary"></i>Abre: {{ $evaluacion->fecha_publicacion?->format('d/m/Y H:i') ?? 'ya disponible' }}</li>
                        <li class="{{ $evaluacion->estaCerrada() ? 'text-danger' : '' }}"><i class="bi bi-calendar-x me-2 text-primary"></i>Cierra: {{ $evaluacion->fecha_cierre?->format('d/m/Y H:i') ?? 'sin fecha de cierre' }}</li>
                    </ul>
                </div>
            </div>

            @if ($evaluacion->instrucciones)
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-2">Instrucciones</h2>
                        <div class="small">@include('partials._texto', ['texto' => $evaluacion->instrucciones])</div>
                    </div>
                </div>
            @endif
        </div>

        @if ($puedeEditar)
            {{-- ===================== Resultados (docente) ===================== --}}
            <div class="col-lg-8">
                <div class="row g-2 mb-3 text-center">
                    <div class="col-4"><div class="card"><div class="card-body py-2"><div class="h4 mb-0">{{ $resumen['rindieron'] }}/{{ $alumnos->count() }}</div><div class="small text-muted">Rindieron</div></div></div></div>
                    <div class="col-4"><div class="card"><div class="card-body py-2"><div class="h4 mb-0 text-warning">{{ $resumen['pendientes'] }}</div><div class="small text-muted">Por corregir</div></div></div></div>
                    <div class="col-4"><div class="card"><div class="card-body py-2"><div class="h4 mb-0 text-success">{{ $resumen['promedio'] }}</div><div class="small text-muted">Promedio</div></div></div></div>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Alumno</th>
                                    <th>Intentos</th>
                                    <th class="text-center">Mejor nota</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($alumnos as $al)
                                    @php
                                        $lista = $intentos[$al->id] ?? collect();
                                        $mejor = $lista->whereNotNull('nota')->sortByDesc(fn ($i) => (float) $i->nota)->first();
                                    @endphp
                                    <tr>
                                        <td>{{ $al->nombre_completo }}</td>
                                        <td>
                                            @forelse ($lista as $int)
                                                <a href="{{ route('intentos.show', $int) }}" class="badge text-decoration-none bg-{{ $int->estado_color }} me-1"
                                                   title="{{ $int->estado_label }} — {{ $int->finalizado_at?->format('d/m H:i') ?? 'rindiendo' }}">
                                                    #{{ $int->numero }} {{ $int->nota_texto ?? $int->estado_label }}
                                                </a>
                                            @empty
                                                <span class="badge bg-light text-muted">Sin rendir</span>
                                            @endforelse
                                        </td>
                                        <td class="text-center fw-semibold">{{ $mejor?->nota_texto ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">El aula todavía no tiene alumnos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            {{-- ===================== Alumno ===================== --}}
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-body p-4 text-center">
                        @if ($puedeRendir)
                            <p class="mb-3">
                                @if ($enCurso)
                                    Tenés un intento en curso.
                                @else
                                    Te quedan <strong>{{ $evaluacion->intentos_permitidos - $usados }}</strong> de {{ $evaluacion->intentos_permitidos }} intentos.
                                @endif
                            </p>
                            <form method="POST" action="{{ route('intentos.comenzar', $evaluacion) }}"
                                  @unless ($enCurso) data-confirmar="{{ $evaluacion->duracion_minutos ? 'Al empezar corre el reloj: tenés ' . $evaluacion->duracion_minutos . ' minutos. ¿Empezar ahora?' : '¿Empezar la evaluación?' }}" @endunless>
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-play-fill me-1"></i> {{ $enCurso ? 'Continuar' : 'Comenzar' }}
                                </button>
                            </form>
                        @elseif ($evaluacion->estaCerrada())
                            <p class="text-danger mb-0"><i class="bi bi-lock me-1"></i>La evaluación está cerrada.</p>
                        @elseif ($usados >= $evaluacion->intentos_permitidos)
                            <p class="text-muted mb-0"><i class="bi bi-check2-all me-1"></i>Ya usaste todos tus intentos.</p>
                        @else
                            <p class="text-muted mb-0">La evaluación todavía no está disponible.</p>
                        @endif
                    </div>
                </div>

                @if ($misIntentos->isNotEmpty())
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h6 fw-bold mb-3">Mis intentos</h2>
                            <ul class="list-group list-group-flush">
                                @foreach ($misIntentos as $int)
                                    <a href="{{ $int->estado === 'en_curso' ? route('intentos.rendir', $int) : route('intentos.show', $int) }}"
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                        <span>
                                            Intento {{ $int->numero }}
                                            <span class="small text-muted d-block">{{ $int->finalizado_at?->format('d/m/Y H:i') ?? 'En curso' }}</span>
                                        </span>
                                        @if ($int->nota_texto !== null)
                                            <span class="badge bg-success fs-6">{{ $int->nota_texto }}/{{ $evaluacion->puntaje_maximo_texto }}</span>
                                        @else
                                            <span class="badge bg-{{ $int->estado_color }}">{{ $int->estado_label }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
