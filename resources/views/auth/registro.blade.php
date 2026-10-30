@extends('layouts.auth')

@section('titulo', 'Crear cuenta')
@section('encabezado', 'Crear cuenta')
@section('subtitulo', 'Registrate gratis en el Aula Virtual')
@section('ancho', 'login-caja-ancha')

@section('contenido')
    @php($tipoActual = old('tipo', $tipo))

    <form method="POST" action="{{ route('registro') }}" data-enviando="Creando cuenta..." novalidate>
        @csrf

        <div class="btn-group w-100 mb-4" role="group" aria-label="Tipo de cuenta">
            <input type="radio" class="btn-check" name="tipo" id="tipoDocente" value="docente"
                   {{ $tipoActual === 'docente' ? 'checked' : '' }}>
            <label class="btn btn-outline-primary" for="tipoDocente">
                <i class="bi bi-person-workspace me-1"></i> Docente
            </label>

            <input type="radio" class="btn-check" name="tipo" id="tipoAlumno" value="alumno"
                   {{ $tipoActual === 'alumno' ? 'checked' : '' }}>
            <label class="btn btn-outline-primary" for="tipoAlumno">
                <i class="bi bi-backpack me-1"></i> Alumno
            </label>

            <input type="radio" class="btn-check" name="tipo" id="tipoInstitucion" value="institucion"
                   {{ $tipoActual === 'institucion' ? 'checked' : '' }}>
            <label class="btn btn-outline-primary" for="tipoInstitucion">
                <i class="bi bi-building me-1"></i> Institución
            </label>
        </div>

        {{-- Datos de la institución --}}
        <div id="bloqueInstitucion" class="border rounded p-3 mb-4 bg-light">
            <h2 class="h6 fw-bold mb-3"><i class="bi bi-building me-1"></i>Datos de la institución</h2>
            <div class="mb-3">
                <label for="inst_nombre" class="form-label">Nombre de la institución</label>
                <input type="text" class="form-control @error('inst_nombre') is-invalid @enderror" id="inst_nombre" name="inst_nombre"
                       value="{{ old('inst_nombre') }}" maxlength="150" placeholder="Ej: EES N° 5 &quot;Manuel Belgrano&quot;">
                @error('inst_nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3">
                <div class="col-sm-4">
                    <label for="inst_cue" class="form-label">CUE <span class="text-muted small">(opcional)</span></label>
                    <input type="text" class="form-control" id="inst_cue" name="inst_cue" value="{{ old('inst_cue') }}" maxlength="20">
                </div>
                <div class="col-sm-4">
                    <label for="inst_nivel" class="form-label">Nivel</label>
                    <select class="form-select" id="inst_nivel" name="inst_nivel">
                        <option value="">—</option>
                        @foreach (\App\Models\Institucion::NIVELES as $valor => $texto)
                            <option value="{{ $valor }}" @selected(old('inst_nivel') === $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4">
                    <label for="inst_localidad" class="form-label">Localidad</label>
                    <input type="text" class="form-control" id="inst_localidad" name="inst_localidad" value="{{ old('inst_localidad') }}" maxlength="100">
                </div>
            </div>
            <p class="small text-muted mt-3 mb-0">Abajo van los datos de quien administra la cuenta (directivo, secretario/a...).</p>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label for="name" class="form-label">Nombre</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name') }}" required maxlength="80" autocomplete="given-name">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-6">
                <label for="apellido" class="form-label">Apellido</label>
                <input type="text" class="form-control @error('apellido') is-invalid @enderror"
                       id="apellido" name="apellido" value="{{ old('apellido') }}" required maxlength="80" autocomplete="family-name">
                @error('apellido')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-3 mb-1">
            <div class="col-sm-7">
                <label for="email" class="form-label">
                    Email <span class="text-muted small" id="emailOpcional">(opcional si cargás el DNI)</span>
                </label>
                <input type="email" class="form-control @error('email') is-invalid @enderror"
                       id="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-5">
                <label for="dni" class="form-label">DNI <span class="text-muted small" id="dniOpcional">(opcional)</span></label>
                <input type="text" class="form-control @error('dni') is-invalid @enderror"
                       id="dni" name="dni" value="{{ old('dni') }}" inputmode="numeric" maxlength="12" placeholder="Sin puntos">
                @error('dni')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="form-text mb-3">Vas a poder ingresar con tu email o con tu DNI.</div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                           id="password" name="password" required minlength="8" autocomplete="new-password">
                    <button class="btn btn-outline-secondary" type="button" data-ver-password="password" tabindex="-1">
                        <i class="bi bi-eye"></i>
                    </button>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-sm-6">
                <label for="password_confirmation" class="form-label">Repetir contraseña</label>
                <input type="password" class="form-control" id="password_confirmation"
                       name="password_confirmation" required minlength="8" autocomplete="new-password">
            </div>
        </div>
        <div class="form-text mt-n2 mb-3">Mínimo 8 caracteres, con letras y números.</div>

        <div class="mb-4" id="bloqueCodigo">
            <label for="codigo" class="form-label">Código de aula <span class="text-muted small">(opcional)</span></label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input type="text" class="form-control text-uppercase @error('codigo') is-invalid @enderror"
                       id="codigo" name="codigo" value="{{ old('codigo', $codigo) }}" maxlength="10"
                       placeholder="Ej: K7M2QX" autocomplete="off">
                @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-text">Si tu docente te pasó un código, ingresalo y quedás inscripto/a en su aula.</div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-person-plus me-1"></i> Crear cuenta
        </button>
    </form>

    <p class="text-center small mt-4 mb-0">
        ¿Ya tenés cuenta? <a href="{{ route('login') }}">Ingresá</a>
    </p>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const actualizar = () => {
        const alumno = document.getElementById('tipoAlumno').checked;
        const inst   = document.getElementById('tipoInstitucion').checked;
        document.getElementById('bloqueCodigo').classList.toggle('d-none', !alumno);
        document.getElementById('bloqueInstitucion').classList.toggle('d-none', !inst);
        document.getElementById('inst_nombre').required = inst;
        document.getElementById('emailOpcional').textContent = alumno ? '(opcional si cargás el DNI)' : '';
        document.getElementById('dniOpcional').textContent = alumno ? '(opcional si cargás el email)' : '(opcional)';
    };
    document.querySelectorAll('input[name="tipo"]').forEach(r => r.addEventListener('change', actualizar));
    actualizar();
});
</script>
@endpush
