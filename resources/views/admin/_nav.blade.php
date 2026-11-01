{{-- Submenú del panel de administración --}}
<ul class="nav nav-pills mb-4 flex-wrap gap-1">
    @foreach ([
        ['admin.panel', 'bi-speedometer2', 'Resumen'],
        ['admin.abonos.index', 'bi-calendar-check', 'Abonos'],
        ['admin.usuarios.index', 'bi-people', 'Usuarios'],
    ] as [$ruta, $icono, $texto])
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs(str_replace('.index', '.*', $ruta)) ? 'active' : '' }}" href="{{ route($ruta) }}">
                <i class="bi {{ $icono }} me-1"></i>{{ $texto }}
            </a>
        </li>
    @endforeach
    <li class="nav-item ms-auto">
        <a class="nav-link" href="{{ config('panel.url') }}" target="_blank" rel="noopener"><i class="bi bi-wallet2 me-1"></i>Cobros (Panel)</a>
    </li>
</ul>
