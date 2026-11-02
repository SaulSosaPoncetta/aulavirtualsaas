<?php

/*
|--------------------------------------------------------------------------
| Datos del prestador (se muestran en Términos y Privacidad)
|--------------------------------------------------------------------------
*/

return [
    'marca'        => env('EMPRESA_MARCA', 'MiGestión'),
    'razon_social' => env('EMPRESA_RAZON_SOCIAL', 'MiGestión'),
    'cuit'         => env('EMPRESA_CUIT', ''),
    'domicilio'    => env('EMPRESA_DOMICILIO', ''),
    'email'        => env('EMPRESA_EMAIL', 'contacto@migestion.com.ar'),
    'jurisdiccion' => env('EMPRESA_JURISDICCION', 'la Provincia de Buenos Aires'),

    // Fecha de la última actualización de los textos legales
    'legales_actualizados' => '01/11/2026',
];
