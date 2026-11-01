<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\Institucion;
use App\Services\PanelPagos;
use Illuminate\Http\Request;

/**
 * Pantallas "Mi abono" y "Abono de la institución": muestran el estado (vencimiento, cupo) y mandan
 * a pagar al Panel de Gestión (ver PagarEnPanelController). Los precios, pagos y comprobantes
 * se gestionan en el Panel.
 */
class AbonoController extends Controller
{
    public function mio(Request $request)
    {
        $user = $request->user();
        abort_unless($user->esDocente(), 403, 'El abono unipersonal es para docentes.');

        return view('abonos.mio', [
            'abono'         => Abono::deUsuario($user),
            'panelPagos'    => PanelPagos::habilitado(),
            'instituciones' => $user->institucionesDocente()->with('abono')->get()->unique('id')->values(),
        ]);
    }

    public function institucion(Request $request, Institucion $institucion)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        $abono = Abono::deInstitucion($institucion);

        return view('abonos.institucion', [
            'institucion' => $institucion,
            'abono'       => $abono,
            'usados'      => $abono->docentesUsados(),
            'panelPagos'  => PanelPagos::habilitado(),
        ]);
    }
}
