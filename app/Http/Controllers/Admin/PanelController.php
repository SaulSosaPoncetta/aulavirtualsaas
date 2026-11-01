<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abono;
use App\Models\Aula;
use App\Models\Institucion;
use App\Models\User;
use Carbon\Carbon;

class PanelController extends Controller
{
    public function index()
    {
        $ahora = Carbon::now('America/Argentina/Buenos_Aires');
        $hoy   = $ahora->copy()->startOfDay();

        $abonos = Abono::with(['usuario', 'institucion'])->whereNotNull('vence_el')->get();

        $stats = [
            'docentes'      => User::role('docente')->count(),
            'alumnos'       => User::role('alumno')->count(),
            'instituciones' => Institucion::count(),
            'aulas'         => Aula::where('estado', 'activa')->count(),
            'activos_uni'   => $abonos->where('tipo', 'unipersonal')->filter->estaActivo()->count(),
            'activos_inst'  => $abonos->where('tipo', 'institucional')->filter->estaActivo()->count(),
            'cupos'         => (int) $abonos->where('tipo', 'institucional')->filter->estaActivo()->sum('cupo_docentes'),
            'vencidos'      => $abonos->reject->estaActivo()->count(),
        ];

        // Vencen en los próximos 10 días
        $porVencer = $abonos->filter(fn ($a) => $a->estaActivo() && $a->diasRestantes() <= 10)
            ->sortBy(fn ($a) => $a->vence_el->getTimestamp())->values();

        return view('admin.panel', compact('stats', 'porVencer'));
    }
}
