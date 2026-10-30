<?php

namespace App\Http\Middleware;

use App\Models\Aula;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el abono que cubre el aula está vencido, el aula queda en solo lectura:
 * bloquea todo lo que crea o publica contenido (POST/PUT/PATCH/DELETE).
 */
class VerificarAbono
{
    /** Acciones permitidas aunque el abono esté vencido (corregir, ordenar, salir...). */
    protected array $permitidas = [
        'logout', 'password.*',
        'aulas.update', 'aulas.archivar', 'aulas.destroy', 'aulas.salir', 'aulas.miembros.baja',
        'entregas.corregir', 'intentos.calificar', 'intentos.finalizar',
        'abonos.*', 'instituciones.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || $request->routeIs(...$this->permitidas) || $request->user()?->esSuperadmin()) {
            return $next($request);
        }

        $aula = $this->aulaDeLaRuta($request);

        if ($aula && ! $aula->escrituraHabilitada()) {
            $msg = $aula->institucion_id
                ? 'El abono de la institución está vencido: el aula está en modo solo lectura.'
                : 'El abono del docente está vencido: el aula está en modo solo lectura.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 402)
                : back()->with('error', $msg);
        }

        return $next($request);
    }

    /** Encuentra el aula a partir de cualquier modelo de la ruta. */
    protected function aulaDeLaRuta(Request $request): ?Aula
    {
        $route = $request->route();
        if (! $route) {
            return null;
        }

        foreach ($route->parameters() as $param) {
            $aula = match (true) {
                $param instanceof Aula                            => $param,
                $param instanceof \App\Models\Clase,
                $param instanceof \App\Models\Tarea,
                $param instanceof \App\Models\Evaluacion,
                $param instanceof \App\Models\Unidad,
                $param instanceof \App\Models\Publicacion         => $param->aula,
                $param instanceof \App\Models\Pregunta            => $param->evaluacion?->aula,
                $param instanceof \App\Models\Intento             => $param->evaluacion?->aula,
                $param instanceof \App\Models\Entrega             => $param->tarea?->aula,
                $param instanceof \App\Models\Comentario          => $param->publicacion?->aula,
                default                                           => null,
            };

            if ($aula) {
                return $aula;
            }
        }

        return null;
    }
}
