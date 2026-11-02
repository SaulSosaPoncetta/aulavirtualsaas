<nav class="navbar navbar-expand-lg navbar-dark navbar-aula sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('inicio') }}">
            <i class="bi bi-mortarboard-fill me-1"></i> Aula Virtual
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#menuPrincipal" aria-controls="menuPrincipal"
                aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('inicio', 'aulas.*') ? 'active' : '' }}" href="{{ route('inicio') }}">
                        <i class="bi bi-journal-bookmark me-1"></i> Mis aulas
                    </a>
                </li>
                @auth
                    @if (auth()->user()->esSuperadmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.panel') }}">
                                <i class="bi bi-shield-lock me-1"></i> Administración
                            </a>
                        </li>
                    @endif
                    @if (auth()->user()->esDocente() || auth()->user()->administraInstituciones())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('instituciones.*') ? 'active' : '' }}" href="{{ route('instituciones.index') }}">
                                <i class="bi bi-building me-1"></i> Instituciones
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>

            <ul class="navbar-nav align-items-lg-center">
                @auth
                    @php
                        $sinLeer = auth()->user()->unreadNotifications()->count();
                        $ultimas = auth()->user()->notifications()->take(8)->get();
                    @endphp
                    <li class="nav-item dropdown me-lg-1">
                        <a class="nav-link position-relative px-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notificaciones">
                            <i class="bi bi-bell fs-5"></i>
                            @if ($sinLeer)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark campanita-contador">
                                    {{ $sinLeer > 9 ? '9+' : $sinLeer }}
                                </span>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-end p-0 campanita-menu">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <strong>Notificaciones</strong>
                                @if ($sinLeer)
                                    <form method="POST" action="{{ route('notificaciones.leer') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0">Marcar leídas</button>
                                    </form>
                                @endif
                            </div>
                            @forelse ($ultimas as $n)
                                <a href="{{ route('notificaciones.ir', $n->id) }}" class="dropdown-item d-flex gap-2 py-2 {{ $n->read_at ? '' : 'notificacion-nueva' }}">
                                    <i class="bi {{ $n->data['icono'] ?? 'bi-bell' }} text-primary mt-1"></i>
                                    <div class="min-w-0">
                                        <div class="small fw-semibold text-truncate">{{ $n->data['titulo'] ?? 'Aviso' }}</div>
                                        <div class="small text-muted text-wrap">{{ \Illuminate\Support\Str::limit($n->data['texto'] ?? '', 80) }}</div>
                                        <div class="text-muted" style="font-size: .75rem">{{ $n->created_at->locale('es')->diffForHumans() }}</div>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center text-muted small py-4">No tenés notificaciones.</div>
                            @endforelse
                            <a href="{{ route('notificaciones.index') }}" class="dropdown-item text-center small border-top py-2">Ver todas</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            @include('partials._avatar', ['u' => auth()->user(), 'tam' => 28]) <span class="ms-1">{{ auth()->user()->nombre_completo }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <span class="dropdown-item-text small text-muted">
                                    {{ ucfirst(str_replace('_', ' ', auth()->user()->getRoleNames()->first() ?? '')) }}
                                </span>
                            </li>
                            <li><a class="dropdown-item" href="{{ route('perfil.edit') }}"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
                            @if (auth()->user()->esDocente())
                                <li><a class="dropdown-item" href="{{ route('abonos.mio') }}"><i class="bi bi-credit-card me-2"></i>Mi abono</a></li>
                            @endif
                            @foreach (auth()->user()->institucionesAdmin()->get()->unique('id') as $instAdm)
                                <li><a class="dropdown-item" href="{{ route('abonos.institucion', $instAdm) }}"><i class="bi bi-building me-2"></i>Abono {{ \Illuminate\Support\Str::limit($instAdm->nombre, 22) }}</a></li>
                            @endforeach
                            <li>
                                <a class="dropdown-item" href="{{ route('password.cambiar') }}">
                                    <i class="bi bi-key me-2"></i>Cambiar contraseña
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('registro') }}">
                            <i class="bi bi-person-plus me-1"></i> Crear cuenta
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
