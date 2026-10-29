@extends('layouts.app')

@section('titulo', $clase->titulo)

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('aulas.show', $aula) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $aula->nombre }}</a>
            <h1 class="mt-1">{{ $clase->titulo }}</h1>
            <div class="small text-muted mt-1">
                @if ($clase->unidad)<i class="bi bi-folder2-open me-1"></i>{{ $clase->unidad->titulo }} · @endif
                {{ ($clase->fecha_publicacion ?? $clase->created_at)->format('d/m/Y H:i') }}
                @if ($clase->autor) · {{ $clase->autor->nombre_completo }} @endif
            </div>
        </div>

        @if ($puedeEditar)
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if ($clase->estado === 'oculta')
                    <span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>Oculta para alumnos</span>
                @elseif ($clase->estado === 'programada')
                    <span class="badge badge-pendiente"><i class="bi bi-clock me-1"></i>Se publica el {{ $clase->fecha_publicacion->format('d/m/Y H:i') }}</span>
                @endif
                <a href="{{ route('clases.edit', $clase) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> Editar</a>
                <form method="POST" action="{{ route('clases.destroy', $clase) }}" data-confirmar="¿Eliminar esta clase y todo su material?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            @if ($clase->contenido)
                <div class="card mb-4">
                    <div class="card-body p-4 contenido-clase">
                        {{-- Texto escapado, con saltos de línea y enlaces clickeables --}}
                        {!! nl2br(preg_replace(
                            '~(https?://[^\s<]+)~',
                            '<a href="$1" target="_blank" rel="noopener">$1</a>',
                            e($clase->contenido)
                        )) !!}
                    </div>
                </div>
            @endif

            @foreach ($clase->recursos->where('tipo', 'video') as $video)
                @if ($video->youtube_id)
                    <div class="card mb-4">
                        <div class="ratio ratio-16x9">
                            <iframe src="https://www.youtube-nocookie.com/embed/{{ $video->youtube_id }}"
                                    title="{{ $video->nombre_visible }}" allowfullscreen loading="lazy"
                                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                        </div>
                        @if ($video->titulo)
                            <div class="card-body py-2 small fw-semibold">{{ $video->titulo }}</div>
                        @endif
                    </div>
                @endif
            @endforeach

            @if (! $clase->contenido && $clase->recursos->isEmpty())
                <div class="card"><div class="card-body text-muted text-center py-5">Esta clase todavía no tiene contenido.</div></div>
            @endif
        </div>

        <div class="col-lg-4">
            @php($material = $clase->recursos->filter(fn ($r) => $r->tipo !== 'video' || ! $r->youtube_id))
            @if ($material->isNotEmpty())
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-paperclip me-1"></i>Material</h2>
                        <ul class="list-unstyled mb-0">
                            @foreach ($material as $r)
                                <li class="d-flex align-items-start gap-2 py-2 border-bottom">
                                    <i class="bi {{ $r->icono }} fs-4"></i>
                                    <div class="flex-grow-1 min-w-0">
                                        @if ($r->tipo === 'archivo')
                                            @php($verEnLinea = preg_match('~^(application/pdf|image/)~', (string) $r->archivo_mime))
                                            <a href="{{ route('recursos.descargar', ['recurso' => $r, 'ver' => $verEnLinea ? 1 : null]) }}"
                                               @if ($verEnLinea) target="_blank" @endif class="d-block text-truncate">{{ $r->nombre_visible }}</a>
                                            <span class="small text-muted">{{ $r->tamano_legible }}</span>
                                            @if ($verEnLinea)
                                                · <a href="{{ route('recursos.descargar', $r) }}" class="small">Descargar</a>
                                            @endif
                                        @else
                                            <a href="{{ $r->url }}" target="_blank" rel="noopener" class="d-block text-truncate">{{ $r->nombre_visible }}</a>
                                            <span class="small text-muted text-truncate d-block">{{ parse_url($r->url, PHP_URL_HOST) }}</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
