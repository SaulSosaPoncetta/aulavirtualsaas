@extends('layouts.auth')

@section('titulo', 'Cambiar contraseña')
@section('encabezado', 'Cambiar contraseña')
@section('icono', 'bi-shield-lock-fill')
@section('subtitulo', 'Hola, ' . auth()->user()->name)

@section('contenido')
    @if ($obligatorio)
        <div class="alert alert-warning small">
            <i class="bi bi-info-circle me-1"></i>
            Te dieron de alta con una contraseña inicial. Para proteger tu cuenta, elegí una nueva antes de continuar.
        </div>
    @endif

    <form method="POST" action="{{ route('password.actualizar') }}" data-enviando="Guardando...">
        @csrf

        <div class="mb-3">
            <label for="actual" class="form-label">Contraseña actual</label>
            <input type="password" class="form-control @error('actual') is-invalid @enderror"
                   id="actual" name="actual" required autocomplete="current-password">
            @error('actual')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="nueva" class="form-label">Contraseña nueva</label>
            <div class="input-group">
                <input type="password" class="form-control @error('nueva') is-invalid @enderror"
                       id="nueva" name="nueva" required minlength="8" autocomplete="new-password">
                <button class="btn btn-outline-secondary" type="button" data-ver-password="nueva" tabindex="-1">
                    <i class="bi bi-eye"></i>
                </button>
                @error('nueva')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-text">Mínimo 8 caracteres, con letras y números. No puede ser tu DNI.</div>
        </div>

        <div class="mb-4">
            <label for="nueva_confirmation" class="form-label">Repetí la contraseña nueva</label>
            <input type="password" class="form-control" id="nueva_confirmation"
                   name="nueva_confirmation" required minlength="8" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-check-lg me-1"></i> Guardar contraseña
        </button>
    </form>

    <div class="d-flex justify-content-between align-items-center mt-3">
        @unless ($obligatorio)
            <a href="{{ route('inicio') }}" class="small">Volver al inicio</a>
        @else
            <span></span>
        @endunless
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link btn-sm text-muted p-0">Cerrar sesión</button>
        </form>
    </div>
@endsection
