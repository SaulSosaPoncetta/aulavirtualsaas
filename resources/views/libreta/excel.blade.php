<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
        <x:Name>Calificaciones</x:Name>
        <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
    </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
    <style>
        td, th { border: 1px solid #999; padding: 4px 6px; font-family: Calibri, Arial, sans-serif; font-size: 11pt; vertical-align: middle; }
        th { background: #0F766E; color: #fff; font-weight: bold; text-align: center; }
        th.manual { background: #B45309; }
        th.cierre { background: #065F46; }
        .titulo { font-size: 14pt; font-weight: bold; border: none; }
        .info { border: none; color: #555; }
        .num { mso-number-format: "0.00"; text-align: center; }
        .texto { text-align: center; color: #777; }
        .falta { text-align: center; color: #C00000; }
        .prom { mso-number-format: "0.00"; text-align: center; font-weight: bold; background: #F0FDFA; }
        .cie { mso-number-format: "0.00"; text-align: center; font-weight: bold; background: #ECFDF5; }
        .pie { background: #E2E8F0; font-weight: bold; }
    </style>
</head>
<body>
@php
    $colspan = $items->count() + ($periodo ? 3 : 2);
    $tipos = ['tarea' => 'Tarea', 'evaluacion' => 'Evaluación', 'manual' => 'Nota'];
    $n = fn ($v) => \App\Models\Evaluacion::numero($v);
    $celdaNum = fn ($v, $clase) => $v !== null
        ? '<td class="' . $clase . '" x:num="' . number_format($v, 2, '.', '') . '">' . number_format($v, 2, ',', '') . '</td>'
        : '<td class="' . $clase . '"></td>';
@endphp
<table>
    <tr><td class="titulo" colspan="{{ $colspan }}">Calificaciones — {{ $aula->nombre }}{{ $periodo ? ' — ' . $periodo->nombre : '' }}</td></tr>
    <tr><td class="info" colspan="{{ $colspan }}">
        {{ $aula->nombre_escuela ? $aula->nombre_escuela . ' · ' : '' }}Ciclo {{ $aula->ciclo_lectivo }}
        {{ $periodo ? '· ' . $periodo->desde->format('d/m/Y') . ' al ' . $periodo->hasta->format('d/m/Y') : '· Todo el ciclo' }}
        · Exportado el {{ $fecha->format('d/m/Y H:i') }}
        {{ $contarCeros ? '· Lo no entregado cuenta como 0' : '' }}
    </td></tr>
    <tr><td class="info" colspan="{{ $colspan }}"></td></tr>

    <tr>
        <th>Alumno</th>
        @foreach ($items as $it)
            <th class="{{ $it['tipo'] === 'manual' ? 'manual' : '' }}">
                {{ $it['titulo'] }}<br>({{ $tipos[$it['tipo']] }} /{{ $n($it['max']) }}{{ $it['peso'] != 1 ? ' · peso ' . $n($it['peso']) : '' }})
            </th>
        @endforeach
        <th>Promedio /10</th>
        @if ($periodo)
            <th class="cierre">Cierre {{ $periodo->nombre }}</th>
        @endif
    </tr>

    @foreach ($alumnos as $al)
        <tr>
            <td>{{ $al->nombre_completo }}</td>
            @foreach ($items as $it)
                @php($c = $celdas[$al->id][$it['clave']])
                @if ($c['nota'] !== null)
                    {!! $celdaNum($c['nota'], 'num') !!}
                @else
                    <td class="{{ $c['estado'] === 'falta' ? 'falta' : 'texto' }}">{{ $c['texto'] === '—' ? '' : $c['texto'] }}</td>
                @endif
            @endforeach
            {!! $celdaNum($promedios[$al->id]['valor'], 'prom') !!}
            @if ($periodo)
                @php($ci = $cierres[$al->id] ?? null)
                {!! $celdaNum($ci?->nota !== null ? (float) $ci->nota : null, 'cie') !!}
            @endif
        </tr>
    @endforeach

    <tr>
        <td class="pie">Promedio del curso</td>
        @foreach ($items as $it)
            {!! $celdaNum($resumenItems[$it['clave']]['promedio'], 'pie num') !!}
        @endforeach
        {!! $celdaNum(collect($promedios)->pluck('valor')->filter(fn ($v) => $v !== null)->avg(), 'pie prom') !!}
        @if ($periodo)
            {!! $celdaNum($cierres->pluck('nota')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->avg(), 'pie cie') !!}
        @endif
    </tr>
</table>
</body>
</html>
