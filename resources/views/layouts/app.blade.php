<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Inicio') — {{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('estilos')
</head>
<body>

    @include('partials.navbar')

    <main class="py-4">
        <div class="container">
            @include('partials.alertas')
            @yield('contenido')
        </div>
    </main>

    <footer class="footer-aula py-3">
        <div class="container d-flex flex-wrap justify-content-between gap-2">
            <span>{{ config('app.name') }} &copy; {{ date('Y') }}</span>
            <span>Un sistema de <strong>MiGestión</strong></span>
        </div>
    </footer>

    @include('partials.modal-confirmar')

    @stack('scripts')
</body>
</html>
