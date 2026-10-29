@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-house-door text-primary me-2"></i>Bienvenido al Aula Virtual</h1>
        <a href="#" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Botón primario</a>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <div class="card card-aula h-100">
                <div class="card-body">
                    <h5 class="card-title">Matemática — 3° A</h5>
                    <p class="card-text text-muted mb-3">Escuela de prueba</p>
                    <span class="badge badge-pendiente"><i class="bi bi-clock me-1"></i>2 tareas pendientes</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card card-aula h-100">
                <div class="card-body">
                    <h5 class="card-title">Lengua — 2° B</h5>
                    <p class="card-text text-muted mb-3">Escuela de prueba</p>
                    <span class="badge bg-info">Al día</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Prueba de componentes</h5>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="btn btn-sm btn-success">Éxito</span>
                        <span class="btn btn-sm btn-warning">Aviso</span>
                        <span class="btn btn-sm btn-danger">Peligro</span>
                    </div>
                    <form action="#" data-confirmar="¿Probamos el modal de confirmación?">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-chat-square-text me-1"></i>Probar modal
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
