<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') — {{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="landing">

<nav class="navbar landing-nav sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('landing') }}">
            <i class="bi bi-mortarboard-fill text-warning me-1"></i> Aula Virtual
        </a>
        <div class="d-flex gap-2">
            @auth
                <a href="{{ route('inicio') }}" class="btn btn-outline-primary btn-sm">Mis aulas</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Ingresar</a>
            @endauth
        </div>
    </div>
</nav>

<main class="py-5">
    <div class="container legal" style="max-width: 820px">
        @yield('contenido')
    </div>
</main>

@include('partials.pie-legal')
</body>
</html>
