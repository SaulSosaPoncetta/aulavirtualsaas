<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\Evaluacion;
use App\Models\Intento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EvaluacionController extends Controller
{
    public function create(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        return view('evaluaciones.form', [
            'aula'       => $aula,
            'evaluacion' => new Evaluacion([
                'unidad_id'           => $request->query('unidad'),
                'intentos_permitidos' => 1,
                'mostrar_resultados'  => 'al_finalizar',
                'puntaje_maximo'      => 10,
                'visible'             => true,
            ]),
            'unidades'   => $aula->unidades()->get(),
        ]);
    }

    public function store(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        $evaluacion = $aula->evaluaciones()->create($this->validar($request, $aula) + ['user_id' => $request->user()->id]);

        return redirect()->route('evaluaciones.preguntas', $evaluacion)
            ->with('exito', 'Evaluación creada. Ahora cargá las preguntas.');
    }

    public function edit(Request $request, Evaluacion $evaluacion)
    {
        abort_unless($evaluacion->aula->puedeEditar($request->user()), 403);

        return view('evaluaciones.form', [
            'aula'       => $evaluacion->aula,
            'evaluacion' => $evaluacion,
            'unidades'   => $evaluacion->aula->unidades()->get(),
        ]);
    }

    public function update(Request $request, Evaluacion $evaluacion)
    {
        $aula = $evaluacion->aula;
        abort_unless($aula->puedeEditar($request->user()), 403);

        $evaluacion->update($this->validar($request, $aula));

        return redirect()->route('evaluaciones.show', $evaluacion)->with('exito', 'Evaluación actualizada.');
    }

    public function destroy(Request $request, Evaluacion $evaluacion)
    {
        $aula = $evaluacion->aula;
        abort_unless($aula->puedeEditar($request->user()), 403);

        $evaluacion->delete();

        return redirect()->route('aulas.show', $aula)->with('exito', 'Evaluación eliminada.');
    }

    /** Editor de preguntas. */
    public function preguntas(Request $request, Evaluacion $evaluacion)
    {
        abort_unless($evaluacion->aula->puedeEditar($request->user()), 403);

        $evaluacion->load('preguntas.opciones');

        return view('evaluaciones.preguntas', [
            'evaluacion'  => $evaluacion,
            'aula'        => $evaluacion->aula,
            'hayIntentos' => $evaluacion->intentos()->where('estado', '!=', 'en_curso')->exists(),
        ]);
    }

    public function show(Request $request, Evaluacion $evaluacion)
    {
        $aula = $evaluacion->aula;
        $user = $request->user();

        abort_unless($aula->puedeVer($user), 403);

        $puedeEditar = $aula->puedeEditar($user);
        abort_unless($puedeEditar || $evaluacion->estaPublicada(), 404);

        $evaluacion->loadCount('preguntas');
        $totalPuntos = $evaluacion->totalPuntos();

        if ($puedeEditar) {
            // Cierra intentos abandonados que ya se pasaron de tiempo
            $evaluacion->intentos()->where('estado', 'en_curso')->get()->each->cerrarSiVencido();

            $alumnos  = $aula->alumnos()->orderBy('apellido')->orderBy('name')->get();
            $intentos = $evaluacion->intentos()->orderBy('numero')->get()->groupBy('alumno_id');

            $notas = $intentos->map(fn ($lista) => $lista->whereNotNull('nota')->max('nota'))->filter(fn ($n) => $n !== null);

            $resumen = [
                'rindieron'  => $intentos->count(),
                'pendientes' => $intentos->flatten()->where('estado', 'pendiente_revision')->count(),
                'promedio'   => $notas->isNotEmpty() ? Evaluacion::numero($notas->avg()) : '—',
            ];

            return view('evaluaciones.show', compact('evaluacion', 'aula', 'puedeEditar', 'totalPuntos', 'alumnos', 'intentos', 'resumen'));
        }

        // Alumno
        $misIntentos = $evaluacion->intentos()->where('alumno_id', $user->id)->orderBy('numero')->get();
        $misIntentos->each->cerrarSiVencido();

        $enCurso    = $misIntentos->firstWhere('estado', 'en_curso');
        $usados     = $misIntentos->count();
        $puedeRendir = $aula->esAlumno($user)
            && $evaluacion->estaAbierta()
            && ($enCurso || $usados < $evaluacion->intentos_permitidos);

        return view('evaluaciones.show', [
            'evaluacion'  => $evaluacion,
            'aula'        => $aula,
            'puedeEditar' => false,
            'totalPuntos' => $totalPuntos,
            'misIntentos' => $misIntentos,
            'enCurso'     => $enCurso,
            'usados'      => $usados,
            'puedeRendir' => $puedeRendir,
        ]);
    }

    protected function validar(Request $request, Aula $aula): array
    {
        $datos = $request->validate([
            'titulo'              => 'required|string|max:200',
            'unidad_id'           => 'nullable|integer|exists:unidades,id,aula_id,' . $aula->id,
            'instrucciones'       => 'nullable|string|max:10000',
            'fecha_publicacion'   => 'nullable|date',
            'fecha_cierre'        => 'nullable|date',
            'duracion_minutos'    => 'nullable|integer|min:1|max:600',
            'intentos_permitidos' => 'required|integer|min:1|max:10',
            'mostrar_resultados'  => 'required|in:' . implode(',', array_keys(Evaluacion::RESULTADOS)),
            'puntaje_maximo'      => 'required|numeric|min:1|max:100',
        ], [], [
            'titulo'              => 'título',
            'unidad_id'           => 'unidad',
            'fecha_publicacion'   => 'fecha de apertura',
            'fecha_cierre'        => 'fecha de cierre',
            'duracion_minutos'    => 'duración',
            'intentos_permitidos' => 'intentos permitidos',
            'puntaje_maximo'      => 'nota máxima',
        ]);

        $tz       = 'America/Argentina/Buenos_Aires';
        $apertura = ! empty($datos['fecha_publicacion']) ? Carbon::parse($datos['fecha_publicacion'], $tz) : null;
        $cierre   = ! empty($datos['fecha_cierre']) ? Carbon::parse($datos['fecha_cierre'], $tz) : null;

        if ($apertura && $cierre && $cierre->lte($apertura)) {
            throw ValidationException::withMessages(['fecha_cierre' => 'El cierre tiene que ser posterior a la apertura.']);
        }

        return [
            'titulo'              => $datos['titulo'],
            'unidad_id'           => $datos['unidad_id'] ?? null,
            'instrucciones'       => $datos['instrucciones'] ?? null,
            'fecha_publicacion'   => $apertura,
            'fecha_cierre'        => $cierre,
            'duracion_minutos'    => $datos['duracion_minutos'] ?? null,
            'intentos_permitidos' => $datos['intentos_permitidos'],
            'mostrar_resultados'  => $datos['mostrar_resultados'],
            'puntaje_maximo'      => $datos['puntaje_maximo'],
            'mezclar_preguntas'   => $request->boolean('mezclar_preguntas'),
            'visible'             => $request->boolean('visible'),
        ];
    }
}
