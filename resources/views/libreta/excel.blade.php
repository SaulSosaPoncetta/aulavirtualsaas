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
        .titulo { font-size: 14pt; font-weight: bold; border: none; }
        .info { border: none; color: #555; }
        .num { mso-number-format: "0.00"; text-align: center; }
        .texto { text-align: center; color: #777; }
        .falta { text-align: center; color: #C00000; }
        .prom { mso-number-format: "0.00"; text-align: center; font-weight: bold; background: #F0FDFA; }
        .pie { background: #E2E8F0; font-weight: bold; }
    </style>
</head>
<body>
@php($colspan = $items->count() + 2)
<table>
    <tr><td class="titulo" colspan="{{ $colspan }}">Calificaciones — {{ $aula->nombre }}</td></tr>
    <tr><td class="info" colspan="{{ $colspan }}">
        {{ $aula->nombre_escuela ? $aula->nombre_escuela . ' · ' : '' }}Ciclo {{ $aula->ciclo_lectivo }} · Exportado el {{ $fecha->format('d/m/Y H:i') }}
        {{ $contarCeros ? '· Lo no entregado cuenta como 0' : '' }}
    </td></tr>
    <tr><td class="info" colspan="{{ $colspan }}"></td></tr>

    <tr>
        <th>Alumno</th>
        @foreach ($items as $it)
            <th>{{ $it['titulo'] }}<br>({{ $it['tipo'] === 'tarea' ? 'Tarea' : 'Evaluación' }} /{{ \App\Models\Evaluacion::numero($it['max']) }})</th>
        @endforeach
        <th>Promedio /10</th>
    </tr>

    @foreach ($alumnos as $al)
        <tr>
            <td>{{ $al->nombre_completo }}</td>
            @foreach ($items as $it)
                @php($c = $celdas[$al->id][$it['clave']])
                @if ($c['nota'] !== null)
                    <td class="num" x:num="{{ number_format($c['nota'], 2, '.', '') }}">{{ number_format($c['nota'], 2, ',', '') }}</td>
                @else
                    <td class="{{ $c['estado'] === 'falta' ? 'falta' : 'texto' }}">{{ $c['texto'] === '—' ? '' : $c['texto'] }}</td>
                @endif
            @endforeach
            @php($pv = $promedios[$al->id]['valor'])
            <td class="prom" @if ($pv !== null) x:num="{{ number_format($pv, 2, '.', '') }}" @endif>{{ $pv !== null ? number_format($pv, 2, ',', '') : '' }}</td>
        </tr>
    @endforeach

    <tr>
        <td class="pie">Promedio del curso</td>
        @foreach ($items as $it)
            @php($p = $resumenItems[$it['clave']]['promedio'])
            <td class="pie num" @if ($p !== null) x:num="{{ number_format($p, 2, '.', '') }}" @endif>{{ $p !== null ? number_format($p, 2, ',', '') : '' }}</td>
        @endforeach
        @php($general = collect($promedios)->pluck('valor')->filter(fn ($v) => $v !== null)->avg())
        <td class="pie prom" @if ($general !== null) x:num="{{ number_format($general, 2, '.', '') }}" @endif>{{ $general !== null ? number_format($general, 2, ',', '') : '' }}</td>
    </tr>
</table>
</body>
</html>
