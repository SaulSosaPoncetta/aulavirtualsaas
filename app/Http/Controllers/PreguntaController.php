<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\Pregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreguntaController extends Controller
{
    public function store(Request $request, Evaluacion $evaluacion)
    {
        abort_unless($evaluacion->aula->puedeEditar($request->user()), 403);

        [$datos, $opciones] = $this->validar($request);

        DB::transaction(function () use ($evaluacion, $datos, $opciones) {
            $pregunta = $evaluacion->preguntas()->create($datos + [
                'orden' => (int) $evaluacion->preguntas()->max('orden') + 1,
            ]);
            $this->guardarOpciones($pregunta, $opciones);
        });

        return redirect()->route('evaluaciones.preguntas', $evaluacion)->with('exito', 'Pregunta agregada.');
    }

    public function update(Request $request, Pregunta $pregunta)
    {
        $evaluacion = $pregunta->evaluacion;
        abort_unless($evaluacion->aula->puedeEditar($request->user()), 403);

        [$datos, $opciones] = $this->validar($request);

        DB::transaction(function () use ($pregunta, $datos, $opciones) {
            $pregunta->update($datos);
            $pregunta->opciones()->delete();
            $this->guardarOpciones($pregunta, $opciones);
        });

        return redirect()->route('evaluaciones.preguntas', $evaluacion)
            ->with('exito', 'Pregunta actualizada. Los intentos ya corregidos no cambian.');
    }

    public function destroy(Request $request, Pregunta $pregunta)
    {
        $evaluacion = $pregunta->evaluacion;
        abort_unless($evaluacion->aula->puedeEditar($request->user()), 403);

        $pregunta->delete();

        return redirect()->route('evaluaciones.preguntas', $evaluacion)->with('exito', 'Pregunta eliminada.');
    }

    public function mover(Request $request, Pregunta $pregunta, string $direccion)
    {
        $evaluacion = $pregunta->evaluacion;
        abort_unless($evaluacion->aula->puedeEditar($request->user()), 403);

        $lista   = $evaluacion->preguntas()->get()->values()->all();
        $pos     = collect($lista)->search(fn ($p) => $p->id == $pregunta->id);
        $destino = $direccion === 'arriba' ? $pos - 1 : $pos + 1;

        if ($pos !== false && isset($lista[$destino])) {
            [$lista[$pos], $lista[$destino]] = [$lista[$destino], $lista[$pos]];
            foreach ($lista as $i => $p) {
                $p->update(['orden' => $i + 1]);
            }
        }

        return redirect()->route('evaluaciones.preguntas', $evaluacion);
    }

    // ===================== Ayudas =====================

    /** @return array{0: array, 1: array} [datos de la pregunta, opciones] */
    protected function validar(Request $request): array
    {
        $request->merge(['puntos' => str_replace(',', '.', (string) $request->puntos)]);

        $datos = $request->validate([
            'tipo'              => 'required|in:' . implode(',', array_keys(Pregunta::TIPOS)),
            'enunciado'         => 'required|string|max:5000',
            'puntos'            => 'required|numeric|min:0.25|max:100',
            'explicacion'       => 'nullable|string|max:5000',
            'opciones'          => 'nullable|array|max:10',
            'opciones.*.texto'  => 'nullable|string|max:500',
            'opciones.*.correcta' => 'nullable',
            'vf'                => 'nullable|in:verdadero,falso',
        ], [], [
            'enunciado'   => 'pregunta',
            'explicacion' => 'explicación',
        ]);

        $opciones = [];

        if (in_array($datos['tipo'], ['unica', 'multiple'])) {
            foreach ((array) ($datos['opciones'] ?? []) as $o) {
                $texto = trim((string) ($o['texto'] ?? ''));
                if ($texto !== '') {
                    $opciones[] = ['texto' => $texto, 'es_correcta' => ! empty($o['correcta'])];
                }
            }

            $correctas = count(array_filter($opciones, fn ($o) => $o['es_correcta']));

            if (count($opciones) < 2) {
                throw ValidationException::withMessages(['opciones' => 'Cargá al menos 2 opciones.']);
            }
            if ($datos['tipo'] === 'unica' && $correctas !== 1) {
                throw ValidationException::withMessages(['opciones' => 'En opción única tiene que haber exactamente 1 opción correcta.']);
            }
            if ($datos['tipo'] === 'multiple' && $correctas < 1) {
                throw ValidationException::withMessages(['opciones' => 'Marcá al menos una opción correcta.']);
            }
        } elseif ($datos['tipo'] === 'verdadero_falso') {
            if (empty($datos['vf'])) {
                throw ValidationException::withMessages(['vf' => 'Indicá si la afirmación es verdadera o falsa.']);
            }
            $opciones = [
                ['texto' => 'Verdadero', 'es_correcta' => $datos['vf'] === 'verdadero'],
                ['texto' => 'Falso',     'es_correcta' => $datos['vf'] === 'falso'],
            ];
        }

        return [[
            'tipo'        => $datos['tipo'],
            'enunciado'   => trim($datos['enunciado']),
            'puntos'      => $datos['puntos'],
            'explicacion' => $datos['explicacion'] ?? null,
        ], $opciones];
    }

    protected function guardarOpciones(Pregunta $pregunta, array $opciones): void
    {
        foreach ($opciones as $i => $o) {
            $pregunta->opciones()->create($o + ['orden' => $i + 1]);
        }
    }
}
