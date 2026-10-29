<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo') — {{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="login-fondo">

<main class="d-flex align-items-center justify-content-center py-5 px-3">
    <div class="login-caja w-100 @yield('ancho')">

        <div class="text-center mb-4">
            <div class="login-logo mb-2"><i class="bi @yield('icono', 'bi-mortarboard-fill')"></i></div>
            <h1 class="h3 fw-bold mb-1">@yield('encabezado', 'Aula Virtual')</h1>
            <p class="text-muted mb-0">@yield('subtitulo')</p>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @include('partials.alertas')
                @yield('contenido')
            </div>
        </div>

        <p class="text-center text-muted small mt-4 mb-0">Un sistema de <strong>MiGestión</strong></p>
    </div>
</main>

<script>
// Mostrar / ocultar contraseña: <button data-ver-password="idDelInput">
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-ver-password]');
    if (!btn) return;
    const input = document.getElementById(btn.dataset.verPassword);
    const visible = input.type === 'text';
    input.type = visible ? 'password' : 'text';
    btn.querySelector('i').className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
});

// Deshabilitar botón al enviar: <form data-enviando="Texto...">
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!form.dataset.enviando) return;
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + form.dataset.enviando;
    }
});
</script>
@stack('scripts')
</body>
</html>
