<?php

/*
|--------------------------------------------------------------------------
| Conexión con el Panel de Gestión (cobros centralizados)
|--------------------------------------------------------------------------
| El Panel es el dueño de los planes, precios, pagos (Mercado Pago y
| transferencia) y vencimientos. Acá solo se guarda cómo mandar al cliente a
| pagar y con qué clave se verifican los avisos que manda el Panel.
|
| PANEL_WEBHOOK_SECRET tiene que ser EXACTAMENTE el "webhook secret" que el
| Panel tiene cargado para el sistema "aulavirtual".
*/

return [

    'url'    => rtrim((string) env('PANEL_URL', 'https://panelgestion.migestion.com.ar'), '/'),
    'slug'   => env('PANEL_SLUG', 'aulavirtual'),
    'secret' => env('PANEL_WEBHOOK_SECRET'),

    // Códigos de plan tal como están cargados en el catálogo del Panel
    'planes' => [
        'unipersonal'   => env('PANEL_PLAN_UNIPERSONAL', 'unipersonal'),
        'institucional' => env('PANEL_PLAN_INSTITUCIONAL', 'institucional'),
    ],

    // Si el Panel da días de gracia (PAGOS_GRACIA_DIAS), poné el mismo número acá
    'gracia_dias' => (int) env('PANEL_GRACIA_DIAS', 0),

    // Tolerancia de reloj para los avisos (segundos)
    'tolerancia' => 300,
];
