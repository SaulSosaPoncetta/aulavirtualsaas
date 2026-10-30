<?php

namespace App\Http\Controllers;

use App\Services\AbonoService;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index(Request $request, AbonoService $precios)
    {
        // Si ya está logueado, va directo a sus aulas
        if ($request->user()) {
            return redirect()->route('inicio');
        }

        $escalas = $precios->escalasInstitucionales();

        return view('landing.index', [
            'planUni'  => $precios->planUnipersonal(),
            'escalas'  => $escalas,
            'desdeInst' => $escalas->min('precio'),
            'escalasJs' => $escalas->map(fn ($e) => [
                'desde'  => (int) $e->docentes_desde,
                'hasta'  => $e->docentes_hasta ? (int) $e->docentes_hasta : null,
                'precio' => (float) $e->precio,
            ])->values(),
        ]);
    }
}
