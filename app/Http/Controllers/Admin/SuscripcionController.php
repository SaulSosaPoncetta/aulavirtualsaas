<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abono;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SuscripcionController extends Controller
{
    public function index(Request $request)
    {
        $tipo   = $request->query('tipo');
        $estado = $request->query('estado');
        $buscar = trim((string) $request->query('buscar'));

        $abonos = Abono::with(['usuario', 'institucion'])
            ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
            ->get()
            ->when($buscar !== '', fn ($c) => $c->filter(fn ($a) => str_contains(mb_strtolower($a->nombre . ' ' . ($a->usuario?->email ?? '')), mb_strtolower($buscar))))
            ->when($estado, fn ($c) => $c->filter(fn ($a) => $a->estado === $estado))
            ->sortBy(fn ($a) => $a->vence_el?->getTimestamp() ?? PHP_INT_MAX)
            ->values();

        return view('admin.abonos', compact('abonos', 'tipo', 'estado', 'buscar'));
    }

    /** Ajuste manual: cortesía, corrección de fecha o de cupo. */
    public function ajustar(Request $request, Abono $abono)
    {
        $datos = $request->validate([
            'vence_el'      => 'nullable|date',
            'cupo_docentes' => 'nullable|integer|min:0|max:' . config('abonos.cupo_maximo', 500),
        ], [], ['vence_el' => 'vencimiento', 'cupo_docentes' => 'cupo']);

        $abono->update([
            'vence_el'      => ! empty($datos['vence_el']) ? Carbon::parse($datos['vence_el'], 'America/Argentina/Buenos_Aires')->toDateString() : null,
            'cupo_docentes' => $abono->tipo === 'institucional' ? (int) ($datos['cupo_docentes'] ?? $abono->cupo_docentes) : null,
        ]);

        return back()->with('exito', "Abono de {$abono->nombre} ajustado.");
    }
}
