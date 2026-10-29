@extends('layouts.auth')

@section('titulo', 'Ingresar')
@section('subtitulo', 'Ingresá a tus aulas')

@section('contenido')
    <form method="POST" action="{{ route('login') }}" data-enviando="Ingresando...">
        @csrf

        <div class="mb-3">
            <label for="usuario" class="form-label">Email o DNI</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="usuario" name="usuario"
                       value="{{ old('usuario') }}" required autofocus autocomplete="username"
                       placeholder="tu@email.com o 40123456">
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <label for="password" class="form-label">Contraseña</label>
                <a href="{{ route('password.request') }}" class="small">¿Te la olvidaste?</a>
            </div>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password"
                       required autocomplete="current-password">
                <button class="btn btn-outline-secondary" type="button" data-ver-password="password" tabindex="-1">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="recordar" id="recordar" value="1">
            <label class="form-check-label" for="recordar">Mantener la sesión iniciada</label>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
        </button>
    </form>

    <hr class="my-4">

    <p class="text-center mb-2 small text-muted">¿No tenés cuenta?</p>
    <div class="row g-2">
        <div class="col-6">
            <a href="{{ route('registro', ['tipo' => 'docente']) }}" class="btn btn-outline-primary w-100">
                <i class="bi bi-person-workspace me-1"></i> Soy docente
            </a>
        </div>
        <div class="col-6">
            <a href="{{ route('registro', ['tipo' => 'alumno']) }}" class="btn btn-outline-primary w-100">
                <i class="bi bi-backpack me-1"></i> Soy alumno
            </a>
        </div>
    </div>
@endsection
