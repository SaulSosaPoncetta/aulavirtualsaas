<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Precios publicados del Aula Virtual, tal como están cargados en el Panel de Gestión.
 * Se usan solo para mostrarlos en la landing; cobrar lo hace siempre el Panel.
 *
 * Se guardan 10 minutos en caché y, si el Panel no responde, se sigue mostrando el último
 * valor conocido (la landing nunca se cae por esto).
 */
class PanelPlanes
{
    protected const CACHE = 'panel.planes';
    protected const ULTIMO = 'panel.planes.ultimo';
    protected const FALLO = 'panel.planes.fallo';

    /** @return object|null  nombre, descripcion, precio */
    public function planUnipersonal(): ?object
    {
        $plan = $this->plan(config('panel.planes.unipersonal'));

        return $plan ? (object) [
            'nombre'      => $plan['nombre'],
            'descripcion' => $plan['descripcion'],
            'precio'      => (float) $plan['precio'],
        ] : null;
    }

    /** @return Collection<object>  escalas con docentes_desde, docentes_hasta, precio, rango_texto */
    public function escalasInstitucionales(): Collection
    {
        $plan = $this->plan(config('panel.planes.institucional'));

        if (! $plan) {
            return collect();
        }

        $escalas = collect($plan['escalas'] ?? []);

        if ($escalas->isEmpty()) {
            $escalas = collect([['desde' => 1, 'hasta' => null, 'precio' => $plan['precio']]]);
        }

        return $escalas->sortBy('desde')->values()->map(function ($e) {
            $desde = (int) $e['desde'];
            $hasta = ! empty($e['hasta']) ? (int) $e['hasta'] : null;

            return (object) [
                'docentes_desde' => $desde,
                'docentes_hasta' => $hasta,
                'precio'         => (float) $e['precio'],
                'rango_texto'    => $hasta ? "De {$desde} a {$hasta} docentes" : "Desde {$desde} docentes",
            ];
        });
    }

    protected function plan(?string $codigo): ?array
    {
        return collect($this->planes())->firstWhere('codigo', $codigo);
    }

    protected function planes(): array
    {
        if (($cache = Cache::get(self::CACHE)) !== null) {
            return $cache;
        }

        // Si el Panel falló hace un momento, no se vuelve a intentar en cada visita
        if (! Cache::get(self::FALLO)) {
            try {
                $r = Http::timeout(4)->acceptJson()
                    ->get(config('panel.url') . '/api/planes-publicos/' . config('panel.slug'));

                if ($r->successful() && is_array($r->json('planes'))) {
                    $planes = $r->json('planes');
                    Cache::put(self::CACHE, $planes, now()->addMinutes(10));
                    Cache::forever(self::ULTIMO, $planes);

                    return $planes;
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudieron leer los precios del Panel', ['error' => $e->getMessage()]);
            }

            Cache::put(self::FALLO, true, now()->addMinute());
        }

        return Cache::get(self::ULTIMO, []);
    }
}
