@extends('layouts.app')

@section('titulo', 'Muro — ' . $aula->nombre)

@section('contenido')
    <div class="page-header">
        <div>
            <a href="{{ route('aulas.show', $aula) }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $aula->nombre }}</a>
            <h1 class="mt-1"><i class="bi bi-megaphone text-primary me-2"></i>Muro</h1>
        </div>
        @if ($esDocente)
            <span class="small text-muted">
                Alumnos: {{ $aula->alumnos_publican ? 'pueden publicar' : 'no publican' }} ·
                {{ $aula->alumnos_comentan ? 'pueden comentar' : 'no comentan' }}
                (<a href="{{ route('aulas.edit', $aula) }}">cambiar</a>)
            </span>
        @endif
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            @if ($puedePublicar && $aula->estado === 'activa')
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" action="{{ route('muro.store', $aula) }}" data-enviando="Publicando...">
                            @csrf
                            <textarea class="form-control mb-2 @error('texto') is-invalid @enderror" name="texto" rows="3" maxlength="5000"
                                      placeholder="{{ $esDocente ? 'Escribí un aviso para tus alumnos...' : 'Compartí algo con tu curso...' }}">{{ old('texto') }}</textarea>
                            @error('texto')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                            <div class="d-flex justify-content-between align-items-center">
                                @if ($esDocente)
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" name="fijada" value="1" id="fijada">
                                        <label class="form-check-label small" for="fijada">Fijar arriba</label>
                                    </div>
                                @else
                                    <span></span>
                                @endif
                                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Publicar</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @forelse ($publicaciones as $pub)
                @include('muro._publicacion', ['pub' => $pub])
            @empty
                <div class="card">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-chat-square-text display-5"></i>
                        <p class="mt-3 mb-0">Todavía no hay publicaciones en el muro.</p>
                    </div>
                </div>
            @endforelse

            <div class="d-flex justify-content-center">
                {{ $publicaciones->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
