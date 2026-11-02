@extends('layouts.app')

@section('titulo', 'Invitación a co-docente')

@section('contenido')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body p-4 text-center">
                    <div class="login-logo mb-3"><i class="bi bi-person-plus-fill"></i></div>
                    <h1 class="h4 fw-bold">Invitación a co-docente</h1>

                    <div class="aula-portada rounded-3 p-3 my-4 text-white text-start" style="background-color: {{ $inv->aula->color }}">
                        <div class="fw-bold fs-5">{{ $inv->aula->nombre }}</div>
                        <div class="small opacity-75">
                            {{ $inv->aula->nombre_escuela ? $inv->aula->nombre_escuela . ' · ' : '' }}Ciclo {{ $inv->aula->ciclo_lectivo }}
                        </div>
                    </div>

                    <p>
                        <strong>{{ $inv->invitador?->nombre_completo ?? 'Un docente' }}</strong> te invitó a compartir esta aula.
                        Vas a poder publicar clases, tareas y evaluaciones, y corregir a los alumnos.
                    </p>

                    @if (! $inv->estaVigente())
                        <div class="alert alert-warning mb-0">
                            Esta invitación ya no está vigente ({{ ['aceptada' => 'ya fue aceptada', 'rechazada' => 'fue rechazada', 'cancelada' => 'fue cancelada'][$inv->estado] ?? 'venció' }}).
                        </div>
                    @elseif (! $emailCoincide)
                        <div class="alert alert-warning mb-0">
                            La invitación es para <strong>{{ $inv->email }}</strong> y vos ingresaste con otra cuenta.
                            Cerrá sesión y entrá (o registrate) con ese email.
                        </div>
                    @else
                        @if ($inv->aula->institucion_id)
                            <p class="small text-muted">Es un aula de <strong>{{ $inv->aula->institucion->nombre }}</strong>: al aceptar, también quedás como docente de la institución.</p>
                        @endif
                        <form method="POST" action="{{ route('invitaciones.responder', $inv->token) }}" class="d-flex justify-content-center gap-2">
                            @csrf
                            <button type="submit" name="respuesta" value="rechazar" class="btn btn-outline-secondary">Rechazar</button>
                            <button type="submit" name="respuesta" value="aceptar" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Aceptar</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
