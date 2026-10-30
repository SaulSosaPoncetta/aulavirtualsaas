@extends('layouts.app')

@section('titulo', 'Instituciones')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-building text-primary me-2"></i>Instituciones</h1>
        <a href="{{ route('instituciones.create') }}" class="btn btn-outline-primary"><i class="bi bi-plus-lg me-1"></i> Crear institución</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            @forelse ($instituciones as $fila)
                @php($inst = $fila['institucion'])
                <div class="card mb-3">
                    <div class="card-body d-flex flex-wrap align-items-center gap-3">
                        @if ($inst->logo)
                            <img src="{{ asset('storage/' . $inst->logo) }}" alt="" class="rounded" style="width: 48px; height: 48px; object-fit: cover">
                        @else
                            <div class="avatar-muro avatar-docente"><i class="bi bi-building"></i></div>
                        @endif
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold">{{ $inst->nombre }}</div>
                            <div class="small text-muted">
                                {{ collect([$inst->nivel_label, $inst->localidad])->filter()->implode(' · ') }}
                                · {{ $inst->aulas_count }} {{ $inst->aulas_count === 1 ? 'aula' : 'aulas' }}
                            </div>
                            <div class="mt-1">
                                @foreach ($fila['roles'] as $rol)
                                    <span class="badge {{ $rol === 'admin' ? 'bg-primary' : 'bg-light text-dark' }}">{{ ['admin' => 'Administrador', 'docente' => 'Docente', 'alumno' => 'Alumno'][$rol] ?? $rol }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            @if ($fila['roles']->contains('admin'))
                                <a href="{{ route('instituciones.show', $inst) }}" class="btn btn-primary btn-sm"><i class="bi bi-speedometer2 me-1"></i> Panel</a>
                            @endif
                            @if ($fila['roles']->contains('docente'))
                                <a href="{{ route('aulas.create', ['institucion' => $inst->id]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Crear aula</a>
                                @unless ($fila['roles']->contains('admin'))
                                    <form method="POST" action="{{ route('instituciones.salir', $inst) }}"
                                          data-confirmar="¿Salir de {{ $inst->nombre }}? Tus aulas no se borran.">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Salir de la institución"><i class="bi bi-box-arrow-right"></i></button>
                                    </form>
                                @endunless
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="card">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-building display-5"></i>
                        <p class="mt-3 mb-0">No formás parte de ninguna institución.<br>
                            Igual podés usar el Aula Virtual por tu cuenta: tus aulas personales funcionan sin institución.</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if (auth()->user()->esDocente())
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-2"><i class="bi bi-key me-1"></i>Sumarme a una institución</h2>
                        <p class="small text-muted">Si tu escuela usa el Aula Virtual, pedile el código de invitación.</p>
                        <form method="POST" action="{{ route('instituciones.unirse') }}">
                            @csrf
                            <input type="text" class="form-control text-uppercase text-center fw-bold mb-2 @error('codigo') is-invalid @enderror"
                                   name="codigo" value="{{ old('codigo') }}" maxlength="10" placeholder="CÓDIGO" required
                                   autocomplete="off" style="letter-spacing: .25em">
                            @error('codigo')<div class="invalid-feedback mb-2">{{ $message }}</div>@enderror
                            <button type="submit" class="btn btn-primary w-100">Sumarme</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
