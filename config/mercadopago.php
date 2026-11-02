<?php

/*
|--------------------------------------------------------------------------
| Mercado Pago (Checkout Pro)
|--------------------------------------------------------------------------
| Credenciales: https://www.mercadopago.com.ar/developers/panel/app
| - MP_ACCESS_TOKEN: "Access token" (TEST-... para pruebas, APP_USR-... en producción)
| - MP_WEBHOOK_SECRET: "Clave secreta" de Webhooks (para validar la firma)
*/

return [
    'access_token'   => env('MP_ACCESS_TOKEN'),
    'webhook_secret' => env('MP_WEBHOOK_SECRET'),

    // Nombre que aparece en el resumen de la tarjeta (máx. 13 caracteres)
    'statement'      => env('MP_STATEMENT', 'AULAVIRTUAL'),

    'api'            => 'https://api.mercadopago.com',
    'timeout'        => 20,
];
