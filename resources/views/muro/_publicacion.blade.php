{{-- Una publicación del muro con sus comentarios --}}
@php
    $soyAutor   = $pub->user_id == auth()->id();
    $esDeDocente = in_array((int) $pub->user_id, $idsDocentes, true);
@endphp
<div class="card mb-3 {{ $pub->fijada ? 'border-primary' : '' }}" id="pub{{ $pub->id }}">
    <div class="card-body">
        <div class="d-flex align-items-start gap-2 mb-2">
            <div class="avatar-muro {{ $esDeDocente ? 'avatar-docente' : '' }}">{{ mb_strtoupper(mb_substr($pub->autor?->name ?? '?', 0, 1)) }}</div>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold">
                    {{ $pub->autor?->nombre_completo ?? 'Usuario eliminado' }}
                    @if ($esDeDocente)<span class="badge bg-primary-subtle text-primary-emphasis ms-1">Docente</span>@endif
                    @if ($pub->fijada)<span class="badge bg-primary ms-1"><i class="bi bi-pin-angle-fill me-1"></i>Fijada</span>@endif
                </div>
                <div class="small text-muted">
                    {{ $pub->created_at->format('d/m/Y H:i') }}
                    @if ($pub->updated_at->gt($pub->created_at->copy()->addMinute())) · editada @endif
                </div>
            </div>

            @if ($soyAutor || $esDocente)
                <div class="dropdown">
                    <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown" aria-label="Opciones">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @if ($soyAutor)
                            <li><button class="dropdown-item" type="button" data-bs-toggle="collapse" data-bs-target="#editar{{ $pub->id }}">
                                <i class="bi bi-pencil me-2"></i>Editar</button></li>
                        @endif
                        @if ($esDocente)
                            <li>
                                <form method="POST" action="{{ route('muro.fijar', $pub) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi {{ $pub->fijada ? 'bi-pin' : 'bi-pin-angle' }} me-2"></i>{{ $pub->fijada ? 'Desfijar' : 'Fijar arriba' }}
                                    </button>
                                </form>
                            </li>
                        @endif
                        <li>
                            <form method="POST" action="{{ route('muro.destroy', $pub) }}" data-confirmar="¿Eliminar esta publicación y sus comentarios?">
                                @csrf @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Eliminar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endif
        </div>

        <div class="contenido-muro">@include('partials._texto', ['texto' => $pub->texto])</div>

        @if ($soyAutor)
            <div class="collapse mt-3" id="editar{{ $pub->id }}">
                <form method="POST" action="{{ route('muro.update', $pub) }}">
                    @csrf @method('PUT')
                    <textarea class="form-control mb-2" name="texto" rows="3" maxlength="5000" required>{{ $pub->texto }}</textarea>
                    <div class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#editar{{ $pub->id }}">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    {{-- Comentarios --}}
    @if ($pub->comentarios->isNotEmpty() || $puedeComentar)
        <div class="card-footer bg-light">
            @foreach ($pub->comentarios as $com)
                <div class="d-flex gap-2 mb-2">
                    <div class="avatar-muro avatar-chico">{{ mb_strtoupper(mb_substr($com->autor?->name ?? '?', 0, 1)) }}</div>
                    <div class="flex-grow-1 min-w-0 bg-white rounded px-2 py-1">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="small fw-semibold">{{ $com->autor?->nombre_completo ?? 'Usuario eliminado' }}</span>
                            <span class="small text-muted text-nowrap">
                                {{ $com->created_at->format('d/m H:i') }}
                                @if ($com->user_id == auth()->id() || $esDocente)
                                    <form method="POST" action="{{ route('muro.comentarios.destroy', $com) }}" class="d-inline"
                                          data-confirmar="¿Eliminar este comentario?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-link btn-sm p-0 ms-1 text-danger" title="Eliminar"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                @endif
                            </span>
                        </div>
                        <div class="small">@include('partials._texto', ['texto' => $com->texto])</div>
                    </div>
                </div>
            @endforeach

            @if ($puedeComentar && $aula->estado === 'activa')
                <form method="POST" action="{{ route('muro.comentar', $pub) }}" class="d-flex gap-2 mt-2">
                    @csrf
                    <input type="text" class="form-control form-control-sm" name="texto" maxlength="2000" required
                           placeholder="Escribí un comentario..." autocomplete="off">
                    <button type="submit" class="btn btn-sm btn-primary" title="Comentar"><i class="bi bi-send"></i></button>
                </form>
            @endif
        </div>
    @endif
</div>
