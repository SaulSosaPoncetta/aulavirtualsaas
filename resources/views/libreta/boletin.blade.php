<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boletín — {{ $aula->nombre }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; color: #1E293B; margin: 0; background: #E2E8F0; font-size: 10.5pt; }
        .barra { position: sticky; top: 0; background: #0F766E; color: #fff; padding: 10px 16px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .barra button, .barra a { background: #fff; color: #0F766E; border: 0; border-radius: 6px; padding: 7px 14px; font-weight: 600; cursor: pointer; text-decoration: none; font-size: 10pt; }
        .barra span { flex: 1; font-size: 10pt; }
        .hoja { background: #fff; width: 210mm; max-width: 100%; min-height: 270mm; margin: 16px auto; padding: 14mm; box-shadow: 0 2px 8px rgba(0,0,0,.12); }
        .enc { display: flex; justify-content: space-between; border-bottom: 3px solid #0F766E; padding-bottom: 8px; margin-bottom: 14px; gap: 12px; }
        .enc h1 { font-size: 16pt; margin: 0 0 2px; color: #0F766E; }
        .enc .der { text-align: right; color: #475569; font-size: 9.5pt; }
        .datos { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; margin-bottom: 14px; }
        .datos div span { color: #64748B; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #CBD5E1; padding: 5px 7px; vertical-align: top; }
        th { background: #F0FDFA; color: #0F766E; text-align: left; font-size: 9.5pt; }
        td.n, th.n { text-align: center; white-space: nowrap; width: 80px; }
        .final td { background: #0F766E; color: #fff; font-weight: 700; font-size: 11.5pt; }
        .baja { color: #B91C1C; font-weight: 700; }
        h2 { font-size: 11.5pt; margin: 18px 0 6px; color: #334155; }
        .detalle td, .detalle th { font-size: 9pt; padding: 3px 6px; }
        .muted { color: #64748B; }
        .firmas { display: flex; justify-content: space-around; margin-top: 50px; }
        .firmas div { border-top: 1px solid #475569; width: 38%; text-align: center; padding-top: 4px; font-size: 9pt; color: #475569; }
        .pie { margin-top: 20px; font-size: 8pt; color: #94A3B8; text-align: center; }
        @media print {
            body { background: #fff; }
            .barra { display: none; }
            .hoja { margin: 0; padding: 0; width: auto; min-height: 0; box-shadow: none; page-break-after: always; break-after: page; }
            .hoja:last-child { page-break-after: auto; break-after: auto; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .final td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
@php
    $num  = fn ($v) => $v === null ? '—' : \App\Models\Evaluacion::numero($v);
    $tipo = ['tarea' => 'Tarea', 'evaluacion' => 'Evaluación', 'manual' => 'Nota'];
@endphp

<div class="barra">
    <span>{{ $boletines->count() === 1 ? 'Boletín de ' . $boletines->first()['alumno']->nombre_completo : $boletines->count() . ' boletines' }} · {{ $aula->nombre }}</span>
    <a href="{{ route('libreta.index', $aula) }}">Volver</a>
    <button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button>
</div>

@foreach ($boletines as $b)
    <div class="hoja">
        <div class="enc">
            <div>
                <h1>Boletín de calificaciones</h1>
                <div>{{ $aula->nombre_escuela ?: config('app.name') }}</div>
            </div>
            <div class="der">
                Ciclo lectivo {{ $aula->ciclo_lectivo }}<br>
                Emitido el {{ $fecha->format('d/m/Y') }}
            </div>
        </div>

        <div class="datos">
            <div><span>Alumno/a:</span> <strong>{{ $b['alumno']->nombre_completo }}</strong></div>
            <div><span>DNI:</span> {{ $b['alumno']->dni ?: '—' }}</div>
            <div><span>Espacio curricular:</span> <strong>{{ $aula->materia }}</strong></div>
            <div><span>Curso:</span> {{ trim(($aula->curso ?? '') . ' ' . ($aula->division ?? '')) ?: '—' }}{{ $aula->turno ? ' · Turno ' . $aula->turno : '' }}</div>
            <div><span>{{ $aula->docentes->count() > 1 ? 'Docentes' : 'Docente' }}:</span> {{ $aula->docentes->map(fn ($d) => $d->nombre_completo)->join(' · ') ?: '—' }}</div>
        </div>

        @if ($periodos->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Período</th>
                        <th class="n">Promedio</th>
                        <th class="n">Nota</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($b['filas'] as $f)
                        <tr>
                            <td><strong>{{ $f['periodo']->nombre }}</strong><br><span class="muted">{{ $f['periodo']->desde->format('d/m') }} al {{ $f['periodo']->hasta->format('d/m') }}</span></td>
                            <td class="n">{{ $num($f['promedio']) }}</td>
                            <td class="n {{ $f['definitiva'] !== null && $f['definitiva'] < 6 ? 'baja' : '' }}"><strong>{{ $num($f['definitiva']) }}</strong></td>
                            <td>{{ $f['observacion'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="final">
                        <td>Calificación final</td>
                        <td class="n"></td>
                        <td class="n">{{ $num($b['final']) }}</td>
                        <td style="font-weight: 400; font-size: 9pt">Promedio de las notas de cada período</td>
                    </tr>
                </tfoot>
            </table>
            <p class="muted" style="font-size: 8.5pt; margin-top: -8px">
                «Promedio»: promedio ponderado de las actividades, en escala 10. «Nota»: la nota de cierre que puso el docente (o el promedio, si no la cargó).
            </p>
        @else
            <table>
                <tr class="final">
                    <td>Promedio general</td>
                    <td class="n">{{ $num($b['general']) }}</td>
                </tr>
            </table>
        @endif

        <h2>Detalle de actividades</h2>
        @php($grupos = $periodos->isNotEmpty() ? $b['filas']->map(fn ($f) => ['nombre' => $f['periodo']->nombre, 'items' => $f['items']]) : collect())
        @if ($b['sinPeriodo']->isNotEmpty())
            @php($grupos->push(['nombre' => $periodos->isNotEmpty() ? 'Fuera de los períodos' : null, 'items' => $b['sinPeriodo']]))
        @endif

        @forelse ($grupos->filter(fn ($g) => $g['items']->isNotEmpty()) as $g)
            <table class="detalle">
                <thead>
                    <tr>
                        <th>{{ $g['nombre'] ?? 'Actividad' }}</th>
                        <th class="n">Fecha</th>
                        <th class="n">Nota</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($g['items'] as $it)
                        @php($c = $b['celdas'][$it['clave']] ?? null)
                        <tr>
                            <td>
                                {{ $it['titulo'] }} <span class="muted">· {{ $tipo[$it['tipo']] }}{{ $it['peso'] != 1 ? ($it['peso'] == 0 ? ' · no cuenta' : ' · ×' . $num($it['peso'])) : '' }}</span>
                            </td>
                            <td class="n">{{ $it['fecha']->format('d/m') }}</td>
                            <td class="n">
                                @if ($c && $c['nota'] !== null)
                                    {{ $c['texto'] }}<span class="muted">/{{ $num($it['max']) }}</span>
                                @else
                                    <span class="muted">{{ $c && $c['texto'] !== '—' ? $c['texto'] : '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <p class="muted">Sin actividades calificadas.</p>
        @endforelse

        <div class="firmas">
            <div>Firma del docente</div>
            <div>Firma del padre, madre o tutor</div>
        </div>

        <div class="pie">Generado con {{ config('app.name') }} · {{ $fecha->format('d/m/Y H:i') }}</div>
    </div>
@endforeach
</body>
</html>
