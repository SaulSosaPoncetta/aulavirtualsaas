@extends('layouts.app')

@section('titulo', 'Calendario')

@section('contenido')
    @php
        $nombreMes = ucfirst($mes->locale('es')->translatedFormat('F Y'));
        $iconos = ['tarea' => 'bi-clipboard-check', 'cierre' => 'bi-lock', 'apertura' => 'bi-unlock', 'clase' => 'bi-journal-text'];
    @endphp

    <div class="page-header">
        <h1><i class="bi bi-calendar3 text-primary me-2"></i>Calendario</h1>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('agenda.calendario', ['mes' => $anterior]) }}" class="btn btn-outline-secondary" title="Mes anterior"><i class="bi bi-chevron-left"></i></a>
            <span class="fw-semibold px-2 text-center" style="min-width: 150px">{{ $nombreMes }}</span>
            <a href="{{ route('agenda.calendario', ['mes' => $siguiente]) }}" class="btn btn-outline-secondary" title="Mes siguiente"><i class="bi bi-chevron-right"></i></a>
            <a href="{{ route('agenda.calendario') }}" class="btn btn-outline-primary">Hoy</a>
        </div>
    </div>

    <div class="small text-muted mb-3 d-flex flex-wrap gap-3">
        <span><i class="bi bi-clipboard-check me-1"></i>Entrega de tarea</span>
        <span><i class="bi bi-unlock me-1"></i>Abre evaluación</span>
        <span><i class="bi bi-lock me-1"></i>Cierra evaluación</span>
        <span><i class="bi bi-journal-text me-1"></i>Clase programada</span>
        <span><i class="bi bi-check-circle-fill text-success me-1"></i>Ya hecha</span>
    </div>

    {{-- Grilla (pantallas medianas y grandes) --}}
    <div class="card d-none d-md-block">
        <div class="calendario">
            @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $d)
                <div class="calendario-cabecera">{{ $d }}</div>
            @endforeach

            @foreach ($dias as $dia)
                @php($clave = $dia->format('Y-m-d'))
                <div class="calendario-dia {{ $dia->month !== $mes->month ? 'otro-mes' : '' }} {{ $clave === $hoy ? 'hoy' : '' }}">
                    <div class="calendario-numero">{{ $dia->day }}</div>
                    @foreach (($eventos[$clave] ?? collect())->take(4) as $e)
                        <a href="{{ $e['url'] }}" class="calendario-evento {{ $e['hecho'] ? 'hecho' : '' }}"
                           style="border-left-color: {{ $e['color'] }}"
                           title="{{ $e['titulo'] }} — {{ $e['aula'] }} — {{ $e['fecha']->format('H:i') }}">
                            <i class="bi {{ $iconos[$e['tipo']] ?? 'bi-dot' }}"></i>
                            {{ $e['fecha']->format('H:i') }} {{ \Illuminate\Support\Str::after($e['titulo'], ': ') }}
                        </a>
                    @endforeach
                    @if (($eventos[$clave] ?? collect())->count() > 4)
                        <div class="small text-muted">+{{ $eventos[$clave]->count() - 4 }} más</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Lista (celulares) --}}
    <div class="card d-md-none">
        <div class="card-body">
            @php($delMes = $eventos->flatten(1)->filter(fn ($e) => $e['fecha']->month === $mes->month))
            @forelse ($delMes as $e)
                @include('agenda._evento', ['e' => $e])
            @empty
                <p class="text-muted text-center mb-0 py-4">No hay fechas en {{ $nombreMes }}.</p>
            @endforelse
        </div>
    </div>
@endsection
