@extends('layouts.auth')

@section('titulo', 'Restablecer contraseña')
@section('encabezado', 'Nueva contraseña')
@section('icono', 'bi-shield-lock-fill')
@section('subtitulo', 'Elegí una contraseña nueva para tu cuenta')

@section('contenido')
    <form method="POST" action="{{ route('password.store') }}" data-enviando="Guardando...">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror"
                   id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Contraseña nueva</label>
            <div class="input-group">
                <input type="password" class="form-control @error('password') is-invalid @enderror"
                       id="password" name="password" required minlength="8" autocomplete="new-password" autofocus>
                <button class="btn btn-outline-secondary" type="button" data-ver-password="password" tabindex="-1">
                    <i class="bi bi-eye"></i>
                </button>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-text">Mínimo 8 caracteres, con letras y números.</div>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Repetí la contraseña</label>
            <input type="password" class="form-control" id="password_confirmation"
                   name="password_confirmation" required minlength="8" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-check-lg me-1"></i> Guardar contraseña
        </button>
    </form>
@endsection
