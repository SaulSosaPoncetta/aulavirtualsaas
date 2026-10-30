<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Precios iniciales (de ejemplo). Se cambian después desde el panel de superadmin.
 * php artisan db:seed --class=PlanesSeeder
 */
class PlanesSeeder extends Seeder
{
    public function run(): void
    {
        if (Plan::exists()) {
            return; // no pisa precios ya cargados
        }

        Plan::create([
            'tipo'        => 'unipersonal',
            'nombre'      => 'Docente',
            'descripcion' => 'Aulas ilimitadas para tus cursos, en todas las escuelas donde des clases.',
            'precio'      => 5000,
            'orden'       => 1,
        ]);

        $escalas = [
            [1, 10, 4500],
            [11, 30, 4000],
            [31, 60, 3500],
            [61, null, 3000],
        ];

        foreach ($escalas as $i => [$desde, $hasta, $precio]) {
            Plan::create([
                'tipo'           => 'institucional',
                'nombre'         => $hasta ? "{$desde} a {$hasta} docentes" : "Más de " . ($desde - 1) . " docentes",
                'precio'         => $precio,
                'docentes_desde' => $desde,
                'docentes_hasta' => $hasta,
                'orden'          => $i + 1,
            ]);
        }
    }
}
