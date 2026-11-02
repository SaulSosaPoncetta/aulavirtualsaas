<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\Institucion;
use App\Models\User;
use App\Services\DuplicarAulaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DuplicarAulaController extends Controller
{
    public function __construct(protected DuplicarAulaService $servicio)
    {
    }

    public function create(Request $request, Aula $aula)
    {
        $user = $request->user();
        abort_unless($aula->puedeEditar($user), 403);

        return view('aulas.duplicar', [
            'aula'           => $aula,
            'resumen'        => $this->servicio->resumen($aula),
            'instituciones'  => $user->institucionesDocente()->with('abono')->get()->unique('id')->filter->abonoActivo()->sortBy('nombre')->values(),
            'personalActivo' => $user->abonoActivo(),
            'cicloSugerido'  => max((int) $aula->ciclo_lectivo + 1, (int) Carbon::now('America/Argentina/Buenos_Aires')->year),
        ]);
    }

    public function store(Request $request, Aula $aula)
    {
        $user = $request->user();
        abort_unless($aula->puedeEditar($user), 403);

        $permitidas = $user->institucionesDocente()->pluck('instituciones.id')->map(fn ($id) => (int) $id)->all();

        $datos = $request->validate([
            'institucion_id' => ['nullable', 'integer', function ($attr, $valor, $fallar) use ($permitidas) {
                if ($valor && ! in_array((int) $valor, $permitidas, true)) {
                    $fallar('No formás parte de esa institución.');
                }
            }],
            'materia'       => 'required|string|max:120',
            'curso'         => 'nullable|string|max:30',
            'division'      => 'nullable|string|max:30',
            'turno'         => 'nullable|in:Mañana,Tarde,Vespertino,Noche',
            'escuela'       => 'nullable|string|max:150',
            'ciclo_lectivo' => 'required|integer|min:2020|max:2100',
            'fechas'        => 'required|in:quitar,correr',
        ], [], ['ciclo_lectivo' => 'ciclo lectivo', 'division' => 'división']);

        $datos['institucion_id'] = ! empty($datos['institucion_id']) ? (int) $datos['institucion_id'] : null;

        if ($error = $this->verificarAbono($user, $datos['institucion_id'])) {
            return back()->withInput()->with('error', $error);
        }

        $opciones = [
            'clases'       => $request->boolean('copiar_clases'),
            'tareas'       => $request->boolean('copiar_tareas'),
            'evaluaciones' => $request->boolean('copiar_evaluaciones'),
            'ocultar'      => $request->boolean('ocultar'),
            'fechas'       => $datos['fechas'],
        ];

        $nueva = $this->servicio->duplicar($aula, $datos, $user, $opciones);

        return redirect()->route('aulas.show', $nueva)->with('exito',
            "Aula duplicada. El código nuevo es {$nueva->codigo_acceso}."
            . ($opciones['ocultar'] ? ' Todo el contenido quedó oculto: revisalo y hacelo visible cuando quieras.' : ''));
    }

    /** Mismas reglas que al crear un aula. */
    protected function verificarAbono(User $user, ?int $institucionId): ?string
    {
        if ($user->esSuperadmin()) {
            return null;
        }

        if (! $institucionId) {
            return $user->abonoActivo() ? null : 'Para crear aulas personales necesitás tu abono unipersonal activo.';
        }

        $inst = Institucion::with('abono')->find($institucionId);

        if (! $inst->abonoActivo()) {
            return "El abono de {$inst->nombre} no está activo.";
        }

        if (! $inst->ocupaLugar($user) && ! $inst->abono->hayCupo()) {
            return "{$inst->nombre} no tiene lugares de docente disponibles.";
        }

        return null;
    }
}
