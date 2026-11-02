<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\User;
use App\Services\Notificador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * El docente restablece la contraseña de un alumno de su aula que NO tiene email confirmado
 * (si tiene email, el alumno la recupera solo desde "¿Te la olvidaste?").
 */
class AlumnoPasswordController extends Controller
{
    public function restablecer(Request $request, Aula $aula, User $alumno, Notificador $notificador)
    {
        $docente = $request->user();

        abort_unless($aula->puedeEditar($docente), 403);
        abort_unless($aula->esAlumno($alumno), 404, 'No es alumno de esta aula.');

        if ($alumno->email && $alumno->email_verified_at) {
            return back()->with('error', "{$alumno->nombre_completo} tiene email confirmado: que use \"¿Te la olvidaste?\" en el ingreso para recuperarla.");
        }

        // No se puede tocar a alguien que también es docente
        if ($alumno->esDocente() || $alumno->esSuperadmin()) {
            return back()->with('error', 'No se puede restablecer la contraseña de un docente desde un aula.');
        }

        $clave = $this->claveTemporal();

        $alumno->forceFill([
            'password'              => $clave,
            'debe_cambiar_password' => true,
            'remember_token'        => null,
        ])->save();

        Log::info('Contraseña de alumno restablecida por docente', [
            'aula' => $aula->id, 'alumno' => $alumno->id, 'docente' => $docente->id, 'ip' => $request->ip(),
        ]);

        // Queda registrado en la campanita del alumno
        $notificador->enviar([$alumno], [
            'tipo'   => 'seguridad',
            'titulo' => 'Contraseña restablecida',
            'texto'  => $docente->nombre_completo . ' restableció tu contraseña. Si no lo pediste, avisale a tu docente.',
            'url'    => route('password.cambiar'),
            'icono'  => 'bi-shield-lock',
            'aula'   => $aula->nombre,
        ], false, $docente->id);

        return back()->with('clave_temporal', [
            'alumno'  => $alumno->nombre_completo,
            'usuario' => $alumno->dni ?? $alumno->email,
            'clave'   => $clave,
        ]);
    }

    /** 6 caracteres fáciles de dictar (sin 0/O ni 1/I/L). */
    protected function claveTemporal(): string
    {
        $letras  = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $numeros = '23456789';
        $todos   = $letras . $numeros;

        // Al menos una letra y un número
        $clave = $letras[random_int(0, strlen($letras) - 1)] . $numeros[random_int(0, strlen($numeros) - 1)];
        for ($i = 0; $i < 4; $i++) {
            $clave .= $todos[random_int(0, strlen($todos) - 1)];
        }

        return str_shuffle($clave);
    }
}
