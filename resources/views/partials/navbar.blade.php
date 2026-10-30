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
                    @if (auth()->user()->esDocente() || auth()->user()->administraInstituciones())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('instituciones.*') ? 'active' : '' }}" href="{{ route('instituciones.index') }}">
                                <i class="bi bi-building me-1"></i> Instituciones
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>

            <ul class="navbar-nav">
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->nombre_completo }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <span class="dropdown-item-text small text-muted">
                                    {{ ucfirst(str_replace('_', ' ', auth()->user()->getRoleNames()->first() ?? '')) }}
                                </span>
                            </li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
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
