<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el usuario tiene email y todavía no lo confirmó, lo manda a la pantalla de verificación.
 * Los alumnos que se registraron solo con DNI no necesitan verificar.
 */
class RequiereEmailVerificado
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->necesitaVerificarEmail()
            && ! $user->esSuperadmin()
            && ! $request->routeIs('verification.*', 'logout', 'password.cambiar', 'password.actualizar')) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
