<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\CierrePeriodo;
use App\Models\NotaManual;
use App\Services\LibretaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LibretaController extends Controller
{
    public function index(Request $request, Aula $aula, LibretaService $libreta)
    {
        $user = $request->user();
        abort_unless($aula->puedeVer($user), 403);

        $esDocente   = $aula->puedeSupervisar($user);
        $contarCeros = $request->boolean('ceros');
        $periodoId   = $this->periodoElegido($request, $aula, $libreta);

        $datos = $libreta->armar($aula, $contarCeros, $esDocente ? null : $user->id, $periodoId);

        return view('libreta.index', $datos + [
            'aula'        => $aula,
            'esDocente'   => $esDocente,
            'puedeCargar' => $aula->puedeEditar($user),
            'contarCeros' => $contarCeros,
        ]);
    }

    /** Guarda de una vez las notas manuales y las notas de cierre del período. */
    public function guardar(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        $request->validate([
            'notas'       => ['nullable', 'array'],
            'cierres'     => ['nullable', 'array'],
            'periodo_id'  => ['nullable', 'integer'],
        ]);

        $alumnos  = $aula->alumnos()->pluck('users.id')->all();
        $columnas = $aula->columnasLibreta()->get()->keyBy('id');
        $errores  = [];
        $cambios  = 0;

        foreach ((array) $request->input('notas', []) as $columnaId => $porAlumno) {
            $col = $columnas[$columnaId] ?? null;
            if (! $col || ! is_array($porAlumno)) {
                continue;
            }

            foreach ($porAlumno as $alumnoId => $valor) {
                if (! in_array((int) $alumnoId, $alumnos)) {
                    continue;
                }

                $nota = $this->leerNota($valor, (float) $col->puntaje_maximo);
                if ($nota === false) {
                    $errores[] = "«{$col->nombre}»: «{$valor}» no es una nota válida (0 a " . \App\Models\Evaluacion::numero($col->puntaje_maximo) . ').';
                    continue;
                }

                $actual = NotaManual::where('columna_id', $col->id)->where('alumno_id', $alumnoId)->first();

                if ($nota === null) {
                    if ($actual && $actual->nota !== null) {
                        $actual->observacion ? $actual->update(['nota' => null]) : $actual->delete();
                        $cambios++;
                    }
                    continue;
                }

                if (! $actual || (float) $actual->nota !== $nota) {
                    NotaManual::updateOrCreate(['columna_id' => $col->id, 'alumno_id' => $alumnoId], ['nota' => $nota]);
                    $cambios++;
                }
            }
        }

        if ($request->filled('periodo_id') && is_array($request->input('cierres'))) {
            $periodo = $aula->periodos()->whereKey($request->integer('periodo_id'))->firstOrFail();

            foreach ($request->input('cierres') as $alumnoId => $valor) {
                if (! in_array((int) $alumnoId, $alumnos)) {
                    continue;
                }

                $nota = $this->leerNota($valor, 10);
                if ($nota === false) {
                    $errores[] = "Nota de cierre: «{$valor}» no es válida (0 a 10).";
                    continue;
                }

                $actual = CierrePeriodo::where('periodo_id', $periodo->id)->where('alumno_id', $alumnoId)->first();

                if ($nota === null) {
                    if ($actual && $actual->nota !== null) {
                        $actual->observacion ? $actual->update(['nota' => null]) : $actual->delete();
                        $cambios++;
                    }
                    continue;
                }

                if (! $actual || (float) $actual->nota !== $nota) {
                    CierrePeriodo::updateOrCreate(['periodo_id' => $periodo->id, 'alumno_id' => $alumnoId], ['nota' => $nota]);
                    $cambios++;
                }
            }
        }

        if ($errores) {
            throw ValidationException::withMessages(['notas' => $errores]);
        }

        return back()->with('ok', $cambios ? "Calificaciones guardadas ({$cambios} " . ($cambios === 1 ? 'cambio' : 'cambios') . ').' : 'No había cambios para guardar.');
    }

    /** Observación de una nota manual o del cierre de un período (para un alumno). */
    public function observacion(Request $request, Aula $aula)
    {
        abort_unless($aula->puedeEditar($request->user()), 403);

        $datos = $request->validate([
            'tipo'        => ['required', 'in:columna,cierre'],
            'id'          => ['required', 'integer'],
            'alumno_id'   => ['required', 'integer'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless($aula->alumnos()->where('users.id', $datos['alumno_id'])->exists(), 404);
        $obs = trim((string) ($datos['observacion'] ?? '')) ?: null;

        if ($datos['tipo'] === 'columna') {
            $col = $aula->columnasLibreta()->whereKey($datos['id'])->firstOrFail();
            NotaManual::updateOrCreate(['columna_id' => $col->id, 'alumno_id' => $datos['alumno_id']], ['observacion' => $obs ? Str::limit($obs, 300, '') : null]);
        } else {
            $p = $aula->periodos()->whereKey($datos['id'])->firstOrFail();
            CierrePeriodo::updateOrCreate(['periodo_id' => $p->id, 'alumno_id' => $datos['alumno_id']], ['observacion' => $obs]);
        }

        return back()->with('ok', 'Observación guardada.');
    }

    /** Exporta a Excel (tabla HTML que Excel abre como planilla). */
    public function exportar(Request $request, Aula $aula, LibretaService $libreta)
    {
        abort_unless($aula->puedeSupervisar($request->user()), 403);

        $contarCeros = $request->boolean('ceros');
        $periodoId   = $this->periodoElegido($request, $aula, $libreta);
        $datos       = $libreta->armar($aula, $contarCeros, null, $periodoId);
        $ahora       = Carbon::now('America/Argentina/Buenos_Aires');

        $nombre = 'Calificaciones ' . Str::slug($aula->nombre, ' ')
            . ($datos['periodo'] ? ' ' . Str::slug($datos['periodo']->nombre, ' ') : '')
            . ' ' . $ahora->format('Y-m-d') . '.xls';

        $html = view('libreta.excel', $datos + [
            'aula'        => $aula,
            'contarCeros' => $contarCeros,
            'fecha'       => $ahora,
        ])->render();

        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    /** Boletín imprimible: de un alumno (?alumno=ID) o de todo el curso. El alumno solo ve el suyo. */
    public function boletin(Request $request, Aula $aula, LibretaService $libreta)
    {
        $user = $request->user();
        abort_unless($aula->puedeVer($user), 403);

        $esDocente = $aula->puedeSupervisar($user);
        $alumnos   = $aula->alumnos()->orderBy('apellido')->orderBy('name')->get();

        if (! $esDocente) {
            $alumnos = $alumnos->where('id', $user->id)->values();
        } elseif ($request->filled('alumno')) {
            $alumnos = $alumnos->where('id', $request->integer('alumno'))->values();
        }

        abort_if($alumnos->isEmpty(), 404);

        $contarCeros = $request->boolean('ceros');
        $boletines   = $alumnos->map(fn ($al) => ['alumno' => $al] + $libreta->boletin($aula, $al->id, $contarCeros));

        return view('libreta.boletin', [
            'aula'      => $aula->loadMissing('docentes'),
            'boletines' => $boletines,
            'periodos'  => $aula->periodos()->get(),
            'fecha'     => Carbon::now('America/Argentina/Buenos_Aires'),
        ]);
    }

    // ===================== Ayudas =====================

    /** null = todo el ciclo. Si no se eligió, muestra el período en curso. */
    protected function periodoElegido(Request $request, Aula $aula, LibretaService $libreta): ?int
    {
        $valor = $request->query('periodo');

        if ($valor === 'todo') {
            return null;
        }

        $periodos = $aula->periodos()->get();

        if ($valor !== null && $periodos->contains('id', (int) $valor)) {
            return (int) $valor;
        }

        return $libreta->periodoActual($periodos)?->id;
    }

    /** float, null (vacío) o false (inválido). Acepta coma decimal. */
    protected function leerNota($valor, float $max): float|null|false
    {
        $valor = trim(str_replace(',', '.', (string) $valor));

        if ($valor === '') {
            return null;
        }

        if (! is_numeric($valor)) {
            return false;
        }

        $n = round((float) $valor, 2);

        return $n < 0 || $n > $max ? false : $n;
    }
}
