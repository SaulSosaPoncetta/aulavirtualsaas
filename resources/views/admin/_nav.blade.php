{{-- Submenú del panel de administración --}}
<ul class="nav nav-pills mb-4 flex-wrap gap-1">
    @foreach ([
        ['admin.panel', 'bi-speedometer2', 'Resumen'],
        ['admin.pagos.index', 'bi-receipt', 'Pagos'],
        ['admin.abonos.index', 'bi-calendar-check', 'Abonos'],
        ['admin.planes.index', 'bi-tags', 'Precios'],
        ['admin.usuarios.index', 'bi-people', 'Usuarios'],
    ] as [$ruta, $icono, $texto])
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs(str_replace('.index', '.*', $ruta)) ? 'active' : '' }}" href="{{ route($ruta) }}">
                <i class="bi {{ $icono }} me-1"></i>{{ $texto }}
                @if ($ruta === 'admin.pagos.index' && ($pend = \App\Models\Pago::where('estado', 'pendiente')->count()))
                    <span class="badge bg-danger ms-1">{{ $pend }}</span>
                @endif
            </a>
        </li>
    @endforeach
</ul>
