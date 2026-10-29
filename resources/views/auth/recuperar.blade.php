@extends('layouts.auth')

@section('titulo', 'Recuperar contraseña')
@section('encabezado', 'Recuperar contraseña')
@section('icono', 'bi-envelope-paper-fill')
@section('subtitulo', 'Te mandamos un enlace a tu email')

@section('contenido')
    <form method="POST" action="{{ route('password.email') }}" data-enviando="Enviando...">
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label">Email de tu cuenta</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control @error('email') is-invalid @enderror"
                       id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-send me-1"></i> Enviar enlace
        </button>
    </form>

    <div class="alert alert-light border small mt-4 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Si te registraste solo con DNI, pedile a tu docente o a tu institución que te restablezcan la contraseña.
    </div>

    <p class="text-center small mt-3 mb-0"><a href="{{ route('login') }}">Volver a ingresar</a></p>
@endsection
