@extends('layouts.auth')

@section('titulo', 'Confirmá tu email')
@section('encabezado', 'Confirmá tu email')
@section('icono', 'bi-envelope-check-fill')
@section('subtitulo', 'Un paso más para activar tu cuenta')

@section('contenido')
    <p class="mb-3">
        Te enviamos un enlace a <strong>{{ auth()->user()->email }}</strong>.
        Abrilo para confirmar tu dirección y empezar a usar el Aula Virtual.
    </p>
    <ul class="small text-muted mb-4">
        <li>Si no lo ves, revisá la carpeta de <strong>spam</strong> o <strong>promociones</strong>.</li>
        <li>El enlace vence en 24 horas.</li>
    </ul>

    <form method="POST" action="{{ route('verification.send') }}" data-enviando="Enviando..." class="mb-3">
        @csrf
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-arrow-repeat me-1"></i> Reenviar el email</button>
    </form>

    <div class="d-flex justify-content-between small">
        <span class="text-muted">¿El email está mal? Escribinos a {{ config('empresa.email') }}.</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link btn-sm p-0">Cerrar sesión</button>
        </form>
    </div>
@endsection
