<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ingresar — {{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="login-fondo">

<main class="d-flex align-items-center justify-content-center py-5 px-3">
    <div class="login-caja w-100">

        <div class="text-center mb-4">
            <div class="login-logo mb-2"><i class="bi bi-mortarboard-fill"></i></div>
            <h1 class="h3 fw-bold mb-1">Aula Virtual</h1>
            <p class="text-muted mb-0">Ingresá con tu cuenta de la escuela</p>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                @include('partials.alertas')

                <form method="POST" action="{{ route('login') }}" id="formLogin">
                    @csrf

                    <div class="btn-group w-100 mb-4" role="group" aria-label="Tipo de usuario">
                        <input type="radio" class="btn-check" name="tipo" id="tipoDocente" value="docente"
                               {{ old('tipo', 'docente') === 'docente' ? 'checked' : '' }}>
                        <label class="btn btn-outline-primary" for="tipoDocente">
                            <i class="bi bi-person-workspace me-1"></i> Docente
                        </label>

                        <input type="radio" class="btn-check" name="tipo" id="tipoAlumno" value="alumno"
                               {{ old('tipo') === 'alumno' ? 'checked' : '' }}>
                        <label class="btn btn-outline-primary" for="tipoAlumno">
                            <i class="bi bi-backpack me-1"></i> Alumno
                        </label>
                    </div>

                    <div class="mb-3">
                        <label for="usuario" class="form-label" id="labelUsuario">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope" id="iconoUsuario"></i></span>
                            <input type="text" class="form-control" id="usuario" name="usuario"
                                   value="{{ old('usuario') }}" required autofocus autocomplete="username">
                        </div>
                        <div class="form-text" id="ayudaUsuario">Usá el mismo email y contraseña de GestiónAula.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" id="verPassword" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="recordar" id="recordar" value="1">
                        <label class="form-check-label" for="recordar">Mantener la sesión iniciada</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2" id="btnIngresar">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-muted small mt-4 mb-0">Un sistema de <strong>MiGestión</strong></p>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const textos = {
        docente: { label: 'Email', icono: 'bi-envelope', ayuda: 'Usá el mismo email y contraseña de GestiónAula.', modo: 'email' },
        alumno:  { label: 'DNI', icono: 'bi-person-vcard', ayuda: 'Ingresá tu DNI sin puntos.', modo: 'numeric' },
    };

    const actualizar = () => {
        const tipo = document.querySelector('input[name="tipo"]:checked').value;
        const t = textos[tipo];
        document.getElementById('labelUsuario').textContent = t.label;
        document.getElementById('iconoUsuario').className = 'bi ' + t.icono;
        document.getElementById('ayudaUsuario').textContent = t.ayuda;
        document.getElementById('usuario').setAttribute('inputmode', t.modo);
    };

    document.querySelectorAll('input[name="tipo"]').forEach(r => r.addEventListener('change', actualizar));
    actualizar();

    document.getElementById('verPassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        this.querySelector('i').className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
    });

    document.getElementById('formLogin').addEventListener('submit', () => {
        const btn = document.getElementById('btnIngresar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Ingresando...';
    });
});
</script>
</body>
</html>
