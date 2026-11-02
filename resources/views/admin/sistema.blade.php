@extends('layouts.app')

@section('titulo', 'Sistema — Administración')

@section('contenido')
    @php($tam = fn ($b) => \App\Http\Controllers\Admin\SistemaController::tamano($b))

    <div class="page-header">
        <h1><i class="bi bi-hdd-stack text-primary me-2"></i>Sistema</h1>
    </div>

    @include('admin._nav')

    <div class="row g-4">
        {{-- ===================== Correo ===================== --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h2 class="h5 mb-0"><i class="bi bi-envelope me-2 text-primary"></i>Correo</h2></div>
                <div class="card-body">
                    @if (in_array($correo['mailer'], ['log', 'array']))
                        <div class="alert alert-warning small">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            El correo está en modo <strong>{{ $correo['mailer'] }}</strong>: los mails no salen, se guardan en el log. Configurá <code>MAIL_MAILER=smtp</code> en el <code>.env</code>.
                        </div>
                    @endif
                    <dl class="row small mb-3">
                        <dt class="col-4">Modo</dt><dd class="col-8">{{ $correo['mailer'] }}</dd>
                        <dt class="col-4">Servidor</dt><dd class="col-8">{{ $correo['host'] }}:{{ $correo['puerto'] }}</dd>
                        <dt class="col-4">Usuario</dt><dd class="col-8">{{ $correo['usuario'] ?: '—' }}</dd>
                        <dt class="col-4">Remitente</dt><dd class="col-8">{{ $correo['nombre'] }} &lt;{{ $correo['remitente'] }}&gt;</dd>
                    </dl>

                    <form method="POST" action="{{ route('admin.sistema.correo') }}" class="input-group">
                        @csrf
                        <input type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email) }}" required>
                        <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Enviar prueba</button>
                    </form>
                    <div class="form-text">La prueba sale en el momento (sin cola). Si falla, acá ves el error del servidor de correo.</div>
                </div>
            </div>
        </div>

        {{-- ===================== Cola ===================== --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h2 class="h5 mb-0"><i class="bi bi-inboxes me-2 text-primary"></i>Cola de envíos</h2></div>
                <div class="card-body">
                    @if ($cola['conexion'] === 'sync')
                        <div class="alert alert-light border small">
                            La cola está en <strong>sync</strong>: cada mail se manda mientras carga la página. En producción usá <code>QUEUE_CONNECTION=database</code>.
                        </div>
                    @endif

                    <div class="row text-center g-2 mb-3">
                        <div class="col-6">
                            <div class="border rounded py-2">
                                <div class="fs-3 fw-bold">{{ $cola['pendientes'] ?? '—' }}</div>
                                <div class="small text-muted">pendientes</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded py-2 {{ $cola['totalFallidos'] ? 'border-danger' : '' }}">
                                <div class="fs-3 fw-bold {{ $cola['totalFallidos'] ? 'text-danger' : '' }}">{{ $cola['totalFallidos'] }}</div>
                                <div class="small text-muted">fallidos</div>
                            </div>
                        </div>
                    </div>

                    @if ($cola['masViejo'] && now()->timestamp - $cola['masViejo'] > 300)
                        <div class="alert alert-danger small">
                            <i class="bi bi-exclamation-octagon me-1"></i>
                            Hay envíos esperando desde hace más de 5 minutos: la cola no se está procesando. Revisá el cron del servidor
                            {{ $cola['porCron'] ? '' : 'o el proceso queue:work' }}.
                        </div>
                    @endif

                    <p class="small text-muted mb-2">
                        {{ $cola['porCron'] ? 'La cola se procesa cada minuto desde el cron (COLA_POR_CRON=true).' : 'La cola la procesa un queue:work aparte (COLA_POR_CRON=false).' }}
                    </p>

                    @if ($cola['fallidos']->isNotEmpty())
                        <div class="list-group list-group-flush small mb-2" style="max-height: 180px; overflow:auto">
                            @foreach ($cola['fallidos'] as $f)
                                @php($tipo = json_decode($f->payload, true)['displayName'] ?? 'Trabajo')
                                <div class="list-group-item px-0">
                                    <div class="fw-semibold">{{ class_basename($tipo) }} <span class="text-muted fw-normal">· {{ \Carbon\Carbon::parse($f->failed_at)->timezone('America/Argentina/Buenos_Aires')->format('d/m H:i') }}</span></div>
                                    <div class="text-danger text-truncate" title="{{ \Illuminate\Support\Str::limit($f->exception, 500) }}">{{ \Illuminate\Support\Str::before($f->exception, "\n") }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('admin.sistema.reintentar') }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Reintentar todos</button>
                            </form>
                            <form method="POST" action="{{ route('admin.sistema.fallidos') }}" data-confirmar="¿Borrar la lista de envíos fallidos? No se van a mandar.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Borrar</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== Respaldos ===================== --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="h5 mb-0"><i class="bi bi-safe me-2 text-primary"></i>Respaldos</h2>
                    <form method="POST" action="{{ route('admin.sistema.respaldo') }}">
                        @csrf
                        <button class="btn btn-sm btn-primary" onclick="this.disabled=true; this.innerHTML='Creando… puede tardar'; this.form.submit();">
                            <i class="bi bi-plus-lg me-1"></i>Crear respaldo ahora
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Todos los días a las 03:30 se crea un .zip con la base de datos (<code>base.sql</code>) y los archivos subidos.
                        Se guardan los últimos {{ $conservar }}. <strong>Bajá uno cada tanto a tu PC</strong>: si se rompe el servidor, los respaldos que están adentro se pierden con él.
                    </p>

                    @if (empty($respaldos))
                        <p class="text-muted mb-0">Todavía no hay respaldos.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Archivo</th><th>Fecha</th><th class="text-end">Tamaño</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($respaldos as $r)
                                        <tr>
                                            <td><i class="bi bi-file-earmark-zip text-warning me-1"></i>{{ $r['nombre'] }}</td>
                                            <td>{{ $r['fecha']->format('d/m/Y H:i') }}</td>
                                            <td class="text-end">{{ $tam($r['tamano']) }}</td>
                                            <td class="text-end text-nowrap">
                                                <a href="{{ route('admin.sistema.respaldo.descargar', $r['nombre']) }}" class="btn btn-sm btn-outline-primary" title="Descargar"><i class="bi bi-download"></i></a>
                                                <form method="POST" action="{{ route('admin.sistema.respaldo.borrar', $r['nombre']) }}" class="d-inline" data-confirmar="¿Eliminar {{ $r['nombre'] }}?">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
