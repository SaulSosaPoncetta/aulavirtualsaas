@extends('layouts.app')

@section('titulo', 'Notificaciones')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-bell text-primary me-2"></i>Notificaciones</h1>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notificaciones.leer') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-check2-all me-1"></i> Marcar todas como leídas</button>
            </form>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="list-group list-group-flush">
                    @forelse ($notificaciones as $n)
                        <a href="{{ route('notificaciones.ir', $n->id) }}"
                           class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $n->read_at ? '' : 'notificacion-nueva' }}">
                            <i class="bi {{ $n->data['icono'] ?? 'bi-bell' }} fs-4 text-primary"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="fw-semibold">{{ $n->data['titulo'] ?? 'Aviso' }}</span>
                                    <span class="small text-muted text-nowrap" title="{{ $n->created_at->timezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }}">
                                        {{ $n->created_at->locale('es')->diffForHumans() }}
                                    </span>
                                </div>
                                @if (! empty($n->data['aula']))<div class="small text-primary">{{ $n->data['aula'] }}</div>@endif
                                <div class="small text-muted">{{ $n->data['texto'] ?? '' }}</div>
                            </div>
                            @unless ($n->read_at)<span class="punto-nuevo" title="Sin leer"></span>@endunless
                        </a>
                    @empty
                        <div class="list-group-item text-center text-muted py-5">
                            <i class="bi bi-bell-slash display-6"></i>
                            <p class="mt-2 mb-0">No tenés notificaciones.</p>
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="mt-3">{{ $notificaciones->links('pagination::bootstrap-5') }}</div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-envelope me-1"></i>Avisos por email</h2>
                    @if (auth()->user()->email)
                        <form method="POST" action="{{ route('notificaciones.preferencias') }}">
                            @csrf @method('PATCH')
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="notificar_email" name="notificar_email" value="1"
                                       @checked(auth()->user()->notificar_email) onchange="this.form.submit()">
                                <label class="form-check-label" for="notificar_email">Recibir avisos importantes por email</label>
                            </div>
                        </form>
                        <p class="small text-muted mb-0">
                            Por email te llegan: contenido nuevo en tus aulas, correcciones, avisos del docente,
                            recordatorios de vencimientos y avisos de tu abono. Todo lo demás queda en la campanita.
                        </p>
                    @else
                        <p class="small text-muted mb-0">Cargá un email en <a href="{{ route('perfil.edit') }}">Mi perfil</a> para recibir avisos por correo.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
