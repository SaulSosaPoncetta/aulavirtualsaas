<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\Intento;
use App\Models\Respuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntentoController extends Controller
{
    /** El alumno empieza un intento (o retoma el que tiene abierto). */
    public function comenzar(Request $request, Evaluacion $evaluacion)
    {
        $user = $request->user();
        abort_unless($evaluacion->aula->esAlumno($user), 403, 'Solo los alumnos del aula pueden rendir.');

        $intentos = $evaluacion->intentos()->where('alumno_id', $user->id)->orderBy('numero')->get();
        $intentos->each->cerrarSiVencido();

        if ($abierto = $intentos->firstWhere('estado', 'en_curso')) {
            return redirect()->route('intentos.rendir', $abierto);
        }

        if (! $evaluacion->estaAbierta()) {
            return back()->with('error', 'La evaluación no está disponible para rendir.');
        }

        if ($intentos->count() >= $evaluacion->intentos_permitidos) {
            return back()->with('error', 'Ya usaste todos los intentos permitidos.');
        }

        $intento = $evaluacion->intentos()->create([
            'alumno_id'   => $user->id,
            'numero'      => $intentos->count() + 1,
            'iniciado_at' => Evaluacion::ahora(),
            'estado'      => 'en_curso',
        ]);

        return redirect()->route('intentos.rendir', $intento);
    }

    /** Pantalla para responder. */
    public function rendir(Request $request, Intento $intento)
    {
        abort_unless($intento->alumno_id == $request->user()->id, 403);

        $intento->cerrarSiVencido();

        if ($intento->estado !== 'en_curso') {
            return redirect()->route('intentos.show', $intento)->with('error', 'Se terminó el tiempo de este intento.');
        }

        $evaluacion = $intento->evaluacion;
        $preguntas  = $evaluacion->preguntas()->with('opciones')->get();

        // Orden mezclado pero estable para este intento (si recarga la página no cambia)
        if ($evaluacion->mezclar_preguntas) {
            $preguntas = $preguntas->sortBy(fn ($p) => crc32($intento->id . '-p-' . $p->id))->values();
            $preguntas->each(function ($p) use ($intento) {
                if (in_array($p->tipo, ['unica', 'multiple'])) {
                    $p->setRelation('opciones', $p->opciones->sortBy(fn ($o) => crc32($intento->id . '-o-' . $o->id))->values());
                }
            });
        }

        return view('intentos.rendir', [
            'intento'    => $intento,
            'evaluacion' => $evaluacion,
            'preguntas'  => $preguntas,
            'restantes'  => $intento->segundosRestantes(),
        ]);
    }

    /** El alumno envía sus respuestas. */
    public function finalizar(Request $request, Intento $intento)
    {
        abort_unless($intento->alumno_id == $request->user()->id, 403);

        if ($intento->estado !== 'en_curso') {
            return redirect()->route('intentos.show', $intento);
        }

        $request->validate(['respuestas' => 'nullable|array']);

        // Fuera de la tolerancia: se cierra sin tomar las respuestas
        $tarde = $intento->fueraDeTiempo();
        $intento->finalizar((array) $request->input('respuestas', []), $tarde);

        return redirect()->route('intentos.show', $intento)->with(
            $tarde ? 'error' : 'exito',
            $tarde ? 'El tiempo ya había terminado: el intento se cerró sin tus respuestas.' : '¡Evaluación entregada!'
        );
    }

    /** Resultado (alumno) o revisión (docente). */
    public function show(Request $request, Intento $intento)
    {
        $user       = $request->user();
        $evaluacion = $intento->evaluacion;
        $aula       = $evaluacion->aula;
        $esDocente  = $aula->puedeEditar($user);

        abort_unless($esDocente || $intento->alumno_id == $user->id, 403);

        $intento->cerrarSiVencido();
        $intento->load(['respuestas', 'alumno']);

        return view('intentos.show', [
            'intento'     => $intento,
            'evaluacion'  => $evaluacion,
            'aula'        => $aula,
            'esDocente'   => $esDocente,
            'preguntas'   => $evaluacion->preguntas()->with('opciones')->get(),
            'respuestas'  => $intento->respuestas->keyBy('pregunta_id'),
            'verDetalle'  => $esDocente || $evaluacion->resultadosVisibles(),
            'totalPuntos' => $evaluacion->totalPuntos(),
        ]);
    }

    /** El docente pone puntos a las respuestas escritas (y puede ajustar cualquier otra). */
    public function calificar(Request $request, Intento $intento)
    {
        $evaluacion = $intento->evaluacion;
        abort_unless($evaluacion->aula->puedeEditar($request->user()), 403);
        abort_if($intento->estado === 'en_curso', 422, 'El alumno todavía está rindiendo.');

        $puntos      = (array) $request->input('puntos', []);
        $comentarios = (array) $request->input('comentario', []);
        $errores     = [];

        DB::transaction(function () use ($intento, $puntos, $comentarios, &$errores) {
            foreach ($intento->respuestas()->with('pregunta')->get() as $r) {
                $max = (float) $r->pregunta->puntos;

                if (array_key_exists($r->id, $puntos) && $puntos[$r->id] !== null && $puntos[$r->id] !== '') {
                    $valor = (float) str_replace(',', '.', (string) $puntos[$r->id]);

                    if ($valor < 0 || $valor > $max) {
                        $errores["puntos.{$r->id}"] = "Los puntos de una respuesta van de 0 a {$r->pregunta->puntos_texto}.";
                        continue;
                    }

                    $r->puntos   = $valor;
                    $r->correcta = $valor >= $max;
                }

                if (array_key_exists($r->id, $comentarios)) {
                    $c = trim((string) $comentarios[$r->id]);
                    $r->comentario = $c !== '' ? mb_substr($c, 0, 5000) : null;
                }

                $r->save();
            }

            $intento->corregido_por = request()->user()->id;
            $intento->recalcular();
        });

        if ($errores) {
            return back()->withErrors($errores)->withInput();
        }

        return redirect()->route('intentos.show', $intento)->with('exito', $intento->estado === 'finalizado'
            ? 'Corrección guardada. Nota: ' . $intento->nota_texto
            : 'Corrección guardada. Todavía faltan respuestas por corregir.');
    }
}
