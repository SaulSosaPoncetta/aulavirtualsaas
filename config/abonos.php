<?php

/*
|--------------------------------------------------------------------------
| Abonos del Aula Virtual
|--------------------------------------------------------------------------
| Los precios se cargan en la tabla "planes" (se editan desde el panel de
| superadmin). Acá van las opciones generales y los datos para transferencia.
*/

return [

    // Cantidad de meses que se pueden pagar por adelantado
    'meses' => [1, 3, 6, 12],

    // Máximo de docentes en un abono institucional
    'cupo_maximo' => 500,

    // Datos que ve el cliente para pagar por transferencia
    'transferencia' => [
        'titular' => env('ABONO_TITULAR', 'MiGestión'),
        'banco'   => env('ABONO_BANCO', ''),
        'cbu'     => env('ABONO_CBU', ''),
        'alias'   => env('ABONO_ALIAS', ''),
        'cuit'    => env('ABONO_CUIT', ''),
    ],

    // Contacto para consultas sobre pagos
    'contacto' => env('ABONO_CONTACTO', 'ventas@migestion.com.ar'),

];
