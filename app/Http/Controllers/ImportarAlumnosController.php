<?php

namespace App\Http\Controllers;

use App\Models\Institucion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Alta masiva de alumnos desde un CSV (Excel → Guardar como → CSV).
 * Columnas: apellido, nombre, dni, email (email opcional).
 * Contraseña inicial = DNI, con cambio obligatorio en el primer ingreso.
 */
class ImportarAlumnosController extends Controller
{
    public const MAX_FILAS = 1000;

    public function form(Request $request, Institucion $institucion)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        return view('instituciones.importar', [
            'institucion' => $institucion,
            'aulas'       => $institucion->aulas()->activas()->orderBy('materia')->get(),
            'resultado'   => session('resultado'),
        ]);
    }

    public function plantilla(Request $request, Institucion $institucion)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        $csv = "\xEF\xBB\xBF" . "apellido;nombre;dni;email\r\n"
            . "Pérez;Juan Martín;45123456;juan.perez@mail.com\r\n"
            . "Gómez;Sofía;46111222;\r\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_alumnos.csv"',
        ]);
    }

    public function procesar(Request $request, Institucion $institucion)
    {
        abort_unless($institucion->esAdmin($request->user()), 403);

        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:2048',
            'aula_id' => 'nullable|integer|exists:aulas,id,institucion_id,' . $institucion->id,
        ], [
            'archivo.mimes' => 'El archivo tiene que ser CSV. En Excel: Archivo → Guardar como → CSV (delimitado por comas).',
        ], [
            'archivo' => 'archivo',
            'aula_id' => 'aula',
        ]);

        $filas = $this->leerCsv($request->file('archivo')->getRealPath());

        if ($filas === null) {
            return back()->with('error', 'No se encontraron las columnas apellido, nombre y dni en la primera fila. Usá la plantilla.');
        }

        if (count($filas) > self::MAX_FILAS) {
            return back()->with('error', 'El archivo tiene más de ' . self::MAX_FILAS . ' alumnos. Dividilo en partes.');
        }

        $aula = $request->filled('aula_id') ? $institucion->aulas()->find($request->aula_id) : null;
        $hoy  = Carbon::now('America/Argentina/Buenos_Aires')->toDateString();

        $resultado = ['creados' => 0, 'vinculados' => 0, 'inscriptos' => 0, 'errores' => []];

        DB::transaction(function () use ($filas, $institucion, $aula, $hoy, &$resultado) {
            foreach ($filas as $linea => $f) {
                $apellido = trim($f['apellido'] ?? '');
                $nombre   = trim($f['nombre'] ?? '');
                $dni      = preg_replace('/\D/', '', (string) ($f['dni'] ?? ''));
                $email    = mb_strtolower(trim($f['email'] ?? ''));

                if ($apellido === '' && $nombre === '' && $dni === '' && $email === '') {
                    continue; // fila vacía
                }

                if ($apellido === '' || $nombre === '') {
                    $resultado['errores'][] = "Fila {$linea}: falta apellido o nombre.";
                    continue;
                }
                if ($dni === '' || strlen($dni) < 6 || strlen($dni) > 10) {
                    $resultado['errores'][] = "Fila {$linea} ({$apellido}, {$nombre}): DNI vacío o inválido.";
                    continue;
                }
                if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $resultado['errores'][] = "Fila {$linea} ({$apellido}, {$nombre}): el email no es válido, se cargó sin email.";
                    $email = '';
                }

                $user = User::where('dni', $dni)->first();

                if (! $user && $email !== '') {
                    $user = User::where('email', $email)->first();
                }

                if ($user) {
                    $resultado['vinculados']++;
                } else {
                    $user = User::create([
                        'name'                  => Str::title(mb_strtolower($nombre)),
                        'apellido'              => Str::title(mb_strtolower($apellido)),
                        'dni'                   => $dni,
                        'email'                 => $email !== '' && ! User::where('email', $email)->exists() ? $email : null,
                        'password'              => $dni,
                        'debe_cambiar_password' => true,
                        'activo'                => true,
                    ]);
                    $resultado['creados']++;
                }

                if (! $user->hasRole('alumno')) {
                    $user->assignRole('alumno');
                }

                $yaEsta = DB::table('institucion_user')
                    ->where('institucion_id', $institucion->id)->where('user_id', $user->id)->where('rol', 'alumno')
                    ->exists();

                if (! $yaEsta) {
                    $institucion->usuarios()->attach($user->id, ['rol' => 'alumno', 'activo' => true]);
                }

                if ($aula) {
                    $pivot = DB::table('aula_user')->where('aula_id', $aula->id)->where('user_id', $user->id)->first();

                    if (! $pivot) {
                        $aula->miembros()->attach($user->id, ['rol' => 'alumno', 'origen' => 'excel', 'estado' => 'activo', 'fecha_alta' => $hoy]);
                        $resultado['inscriptos']++;
                    } elseif ($pivot->estado !== 'activo') {
                        $aula->miembros()->updateExistingPivot($user->id, ['estado' => 'activo', 'fecha_alta' => $hoy, 'fecha_baja' => null]);
                        $resultado['inscriptos']++;
                    }
                }
            }
        });

        return redirect()->route('instituciones.importar', $institucion)->with('resultado', $resultado + [
            'aula' => $aula?->nombre,
        ]);
    }

    /**
     * Lee el CSV: detecta separador (; , o tabulación), codificación (UTF-8 o Windows-1252)
     * y columnas por nombre. Devuelve [número de fila => ['apellido'=>..., ...]] o null si faltan columnas.
     */
    protected function leerCsv(string $ruta): ?array
    {
        $contenido = file_get_contents($ruta);

        if (! mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }

        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        $lineas    = preg_split('/\r\n|\r|\n/', $contenido);
        $cabecera  = array_shift($lineas) ?? '';

        $separador = collect([';', ',', "\t"])->sortByDesc(fn ($s) => substr_count($cabecera, $s))->first();

        $normalizar = fn ($t) => preg_replace('/[^a-z]/', '', Str::ascii(mb_strtolower(trim($t))));
        $columnas   = array_map($normalizar, str_getcsv($cabecera, $separador));

        $alias = [
            'apellido' => ['apellido', 'apellidos'],
            'nombre'   => ['nombre', 'nombres'],
            'dni'      => ['dni', 'documento', 'nrodocumento', 'numerodocumento'],
            'email'    => ['email', 'mail', 'correo', 'correoelectronico'],
        ];

        $indices = [];
        foreach ($alias as $campo => $opciones) {
            foreach ($columnas as $i => $col) {
                if (in_array($col, $opciones, true)) {
                    $indices[$campo] = $i;
                    break;
                }
            }
        }

        if (! isset($indices['apellido'], $indices['nombre'], $indices['dni'])) {
            return null;
        }

        $filas = [];
        foreach ($lineas as $n => $linea) {
            if (trim($linea) === '') {
                continue;
            }
            $valores = str_getcsv($linea, $separador);
            $fila = [];
            foreach ($indices as $campo => $i) {
                $fila[$campo] = $valores[$i] ?? '';
            }
            $filas[$n + 2] = $fila; // +2: la fila 1 es la cabecera
        }

        return $filas;
    }
}
