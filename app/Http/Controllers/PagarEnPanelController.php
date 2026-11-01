<?php

namespace App\Http\Controllers;

use App\Models\Institucion;
use App\Services\PanelPagos;
use Illuminate\Http\Request;

/**
 * Manda al cliente a pagar (o renovar / ampliar) a la página del Panel con un enlace firmado.
 */
class PagarEnPanelController extends Controller
{
    public function mio(Request $request, PanelPagos $panel)
    {
        $user = $request->user();
        abort_unless($user->esDocente(), 403, 'El abono unipersonal es para docentes.');
        abort_unless(PanelPagos::habilitado(), 404);

        return redirect()->away($panel->urlParaUsuario($user));
    }

    public function institucion(Request $request, Institucion $institucion, PanelPagos $panel)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);
        abort_unless(PanelPagos::habilitado(), 404);

        return redirect()->away($panel->urlParaInstitucion($institucion, $request->user()));
    }
}
