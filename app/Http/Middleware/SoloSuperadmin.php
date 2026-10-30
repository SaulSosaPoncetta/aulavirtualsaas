<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SoloSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->esSuperadmin(), 403, 'Solo para el administrador del sistema.');

        return $next($request);
    }
}
