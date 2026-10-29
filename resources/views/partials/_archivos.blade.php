{{-- Lista de archivos (tarea o entrega). Uso: @include('partials._archivos', ['archivos' => $x]) --}}
<ul class="list-unstyled mb-0">
    @foreach ($archivos as $a)
        <li class="d-flex align-items-start gap-2 py-2 border-bottom">
            <i class="bi {{ $a->icono }} fs-4"></i>
            <div class="flex-grow-1 min-w-0">
                <a href="{{ route('archivos.descargar', ['archivo' => $a, 'ver' => $a->se_ve_en_linea ? 1 : null]) }}"
                   @if ($a->se_ve_en_linea) target="_blank" @endif class="d-block text-truncate">{{ $a->nombre }}</a>
                <span class="small text-muted">{{ $a->tamano_legible }}</span>
                @if ($a->se_ve_en_linea)
                    · <a href="{{ route('archivos.descargar', $a) }}" class="small">Descargar</a>
                @endif
            </div>
        </li>
    @endforeach
</ul>
