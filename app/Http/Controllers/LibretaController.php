<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Services\LibretaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LibretaController extends Controller
{
    public function index(Request $request, Aula $aula, LibretaService $libreta)
    {
        $user = $request->user();
        abort_unless($aula->puedeVer($user), 403);

        $esDocente   = $aula->puedeEditar($user);
        $contarCeros = $request->boolean('ceros');

        // El alumno solo ve su propia fila
        $datos = $libreta->armar($aula, $contarCeros, $esDocente ? null : $user->id);

        return view('libreta.index', $datos + [
            'aula'        => $aula,
            'esDocente'   => $esDocente,
            'contarCeros' => $contarCeros,
        ]);
    }

    /** Exporta a Excel (tabla HTML que Excel abre como planilla, sin paquetes externos). */
    public function exportar(Request $request, Aula $aula, LibretaService $libreta)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        $contarCeros = $request->boolean('ceros');
        $datos       = $libreta->armar($aula, $contarCeros);

        $nombre = 'Calificaciones ' . Str::slug($aula->nombre, ' ') . ' ' . Carbon::now('America/Argentina/Buenos_Aires')->format('Y-m-d') . '.xls';

        $html = view('libreta.excel', $datos + [
            'aula'        => $aula,
            'contarCeros' => $contarCeros,
            'fecha'       => Carbon::now('America/Argentina/Buenos_Aires'),
        ])->render();

        // BOM UTF-8 para que Excel muestre bien tildes y eñes
        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
            'Cache-Control'       => 'no-store',
        ]);
    }
}
