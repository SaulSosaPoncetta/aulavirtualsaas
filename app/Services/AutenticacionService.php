<?php

namespace App\Services;

use App\Models\Institucion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Crea o actualiza los usuarios locales a partir de los datos
 * que devuelven GestiónAula (docentes) y el SGI (alumnos).
 */
class AutenticacionService
{
    public function sincronizarDocente(array $d): User
    {
        return DB::transaction(function () use ($d) {
            $user = User::where('gestionaula_id', $d['id'])->first()
                ?? ($d['email'] ? User::where('email', $d['email'])->first() : null)
                ?? ($d['dni'] ? User::where('dni', $d['dni'])->first() : null)
                ?? new User();

            $user->fill([
                'name'             => $d['nombre'] ?: ($user->name ?? 'Docente'),
                'apellido'         => $d['apellido'] ?? $user->apellido,
                'email'            => $d['email'] ?? $user->email,
                'dni'              => $d['dni'] ?: $user->dni,
                'gestionaula_id'   => $d['id'],
                'origen'           => $user->exists && $user->origen === 'local' && $user->password ? 'local' : 'gestionaula',
                'activo'           => $user->exists ? $user->activo : true,
                'ultimo_acceso_at' => Carbon::now('America/Argentina/Buenos_Aires'),
            ]);
            $user->save();

            if (! $user->hasRole('docente')) {
                $user->assignRole('docente');
            }

            return $user;
        });
    }

    public function sincronizarAlumno(array $a, ?array $inst): User
    {
        return DB::transaction(function () use ($a, $inst) {
            $user = User::where('sgi_id', $a['id'])->first()
                ?? User::where('dni', $a['dni'])->first()
                ?? new User();

            $user->fill([
                'name'             => $a['nombre'] ?: ($user->name ?? 'Alumno'),
                'apellido'         => $a['apellido'] ?? $user->apellido,
                'dni'              => $a['dni'],
                'email'            => $user->email ?? ($a['email'] && ! User::where('email', $a['email'])->exists() ? $a['email'] : null),
                'sgi_id'           => $a['id'],
                'origen'           => 'sgi',
                'activo'           => $user->exists ? $user->activo : true,
                'ultimo_acceso_at' => Carbon::now('America/Argentina/Buenos_Aires'),
            ]);
            $user->save();

            if (! $user->hasRole('alumno')) {
                $user->assignRole('alumno');
            }

            if ($inst) {
                $institucion = Institucion::updateOrCreate(
                    ['sgi_id' => $inst['id']],
                    [
                        'nombre'          => $inst['nombre'],
                        'cue'             => $inst['cue'],
                        'nivel'           => $inst['nivel'],
                        'localidad'       => $inst['localidad'],
                        'origen'          => 'sgi',
                        'sincronizado_at' => Carbon::now('America/Argentina/Buenos_Aires'),
                    ]
                );

                if (! $user->instituciones()->where('institucion_id', $institucion->id)->wherePivot('rol', 'alumno')->exists()) {
                    $user->instituciones()->attach($institucion->id, ['rol' => 'alumno', 'activo' => true]);
                }
            }

            return $user;
        });
    }
}
