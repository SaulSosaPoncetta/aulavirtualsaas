<?php

namespace App\Http\Controllers;

use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function __construct(protected AgendaService $agenda)
    {
    }

    public function pendientes(Request $request)
    {
        $user = $request->user();

        return view('agenda.pendientes', [
            'alumno'  => $this->agenda->aulasIds($user, 'alumno')->isNotEmpty() ? $this->agenda->pendientesAlumno($user) : null,
            'docente' => $this->agenda->aulasIds($user, 'docente')->isNotEmpty() ? $this->agenda->pendientesDocente($user) : null,
        ]);
    }

    public function calendario(Request $request)
    {
        $tz = 'America/Argentina/Buenos_Aires';

        try {
            $mes = $request->filled('mes') ? Carbon::createFromFormat('Y-m', $request->mes, $tz)->startOfMonth() : Carbon::now($tz)->startOfMonth();
        } catch (\Throwable) {
            $mes = Carbon::now($tz)->startOfMonth();
        }

        // Grilla de lunes a domingo
        $inicio = $mes->copy()->startOfWeek(Carbon::MONDAY);
        $fin    = $mes->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $eventos = $this->agenda->eventos($request->user(), $inicio, $fin)
            ->groupBy(fn ($e) => $e['fecha']->format('Y-m-d'));

        $dias = collect();
        for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
            $dias->push($d->copy());
        }

        return view('agenda.calendario', [
            'mes'      => $mes,
            'dias'     => $dias,
            'eventos'  => $eventos,
            'hoy'      => Carbon::now($tz)->format('Y-m-d'),
            'anterior' => $mes->copy()->subMonth()->format('Y-m'),
            'siguiente'=> $mes->copy()->addMonth()->format('Y-m'),
        ]);
    }
}
