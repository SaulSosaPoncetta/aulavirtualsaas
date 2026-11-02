@extends('layouts.app')

@section('titulo', 'Mi perfil')

@section('contenido')
    <div class="page-header">
        <h1><i class="bi bi-person-circle text-primary me-2"></i>Mi perfil</h1>
    </div>

    <div class="row g-4">
        {{-- Foto y resumen --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center p-4">
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="Foto de perfil" class="avatar-perfil mb-3">
                    @else
                        <div class="avatar-perfil avatar-perfil-iniciales mb-3">{{ $user->iniciales }}</div>
                    @endif

                    <div class="fw-semibold fs-5">{{ $user->nombre_completo }}</div>
                    <div class="small text-muted mb-3">
                        {{ $user->getRoleNames()->map(fn ($r) => ['docente' => 'Docente', 'alumno' => 'Alumno', 'superadmin' => 'Administrador del sistema'][$r] ?? $r)->implode(' · ') }}
                    </div>

                    <form method="POST" action="{{ route('perfil.foto') }}" enctype="multipart/form-data" id="formFoto">
                        @csrf
                        <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/webp" class="d-none"
                               onchange="document.getElementById('formFoto').submit()">
                        <label for="avatar" class="btn btn-sm btn-outline-primary mb-1">
                            <i class="bi bi-camera me-1"></i> {{ $user->avatar ? 'Cambiar foto' : 'Subir foto' }}
                        </label>
                    </form>
                    @error('avatar')<div class="text-danger small">{{ $message }}</div>@enderror

                    @if ($user->avatar)
                        <form method="POST" action="{{ route('perfil.foto.quitar') }}" data-confirmar="¿Quitar tu foto de perfil?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger">Quitar foto</button>
                        </form>
                    @endif
                    <div class="small text-muted mt-1">JPG, PNG o WEBP · hasta 2 MB</div>
                </div>
            </div>

            <div class="card">
                <div class="card-body small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Cuenta creada</span><span>{{ $user->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Email</span>
                        @if (! $user->email)
                            <span>—</span>
                        @elseif ($user->email_verified_at)
                            <span class="text-success"><i class="bi bi-patch-check me-1"></i>Confirmado</span>
                        @else
                            <a href="{{ route('verification.notice') }}" class="text-warning">Sin confirmar</a>
                        @endif
                    </div>
                    @if ($user->esDocente())
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Abono</span>
                            <a href="{{ route('abonos.mio') }}">
                                {{ $user->abono?->vence_el ? 'Vence ' . $user->abono->vence_el->format('d/m/Y') : 'Sin abono' }}
                            </a>
                        </div>
                    @endif
                    @foreach ($instituciones as $inst)
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-truncate me-2">{{ $inst['nombre'] }}</span>
                            <span class="text-muted text-nowrap">{{ $inst['roles']->map(fn ($r) => ['admin' => 'Admin', 'docente' => 'Docente', 'alumno' => 'Alumno'][$r] ?? $r)->implode(', ') }}</span>
                        </div>
                    @endforeach
                    <a href="{{ route('password.cambiar') }}" class="btn btn-sm btn-outline-secondary w-100 mt-3">
                        <i class="bi bi-key me-1"></i> Cambiar contraseña
                    </a>
                </div>
            </div>
        </div>

        {{-- Datos --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Mis datos</h2>

                    <form method="POST" action="{{ route('perfil.update') }}" data-enviando="Guardando...">
                        @csrf @method('PUT')

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="name" class="form-label">Nombre</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                       value="{{ old('name', $user->name) }}" required maxlength="80" autocomplete="given-name">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input type="text" class="form-control @error('apellido') is-invalid @enderror" id="apellido" name="apellido"
                                       value="{{ old('apellido', $user->apellido) }}" required maxlength="80" autocomplete="family-name">
                                @error('apellido')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-7">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                                       value="{{ old('email', $user->email) }}" maxlength="150" autocomplete="email">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Si lo cambiás, te vamos a pedir que confirmes el nuevo.</div>
                            </div>
                            <div class="col-sm-5">
                                <label for="dni" class="form-label">DNI</label>
                                <input type="text" class="form-control @error('dni') is-invalid @enderror" id="dni" name="dni"
                                       value="{{ old('dni', $user->dni) }}" inputmode="numeric" maxlength="12" placeholder="Sin puntos">
                                @error('dni')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">También podés ingresar con tu DNI.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label for="telefono" class="form-label">Teléfono <span class="text-muted small">(opcional)</span></label>
                                <input type="tel" class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono"
                                       value="{{ old('telefono', $user->telefono) }}" maxlength="40" autocomplete="tel">
                                @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento <span class="text-muted small">(opcional)</span></label>
                                <input type="date" class="form-control @error('fecha_nacimiento') is-invalid @enderror" id="fecha_nacimiento" name="fecha_nacimiento"
                                       value="{{ old('fecha_nacimiento', $user->fecha_nacimiento?->format('Y-m-d')) }}">
                                @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
