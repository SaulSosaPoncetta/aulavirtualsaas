<?php

return [

    /*
    | Respaldos automáticos: base de datos + archivos subidos, en un solo .zip
    | guardado en storage/app/respaldos (no es público).
    */
    'respaldos' => [
        // Cuántos respaldos conservar (se borran los más viejos)
        'conservar' => (int) env('RESPALDO_CONSERVAR', 14),

        // Ruta de mysqldump. En el VPS alcanza con "mysqldump".
        // En Windows con XAMPP: C:\xampp\mysql\bin\mysqldump.exe
        'mysqldump' => env('RESPALDO_MYSQLDUMP', 'mysqldump'),

        // Incluir los archivos subidos (entregas, materiales, fotos)
        'incluir_archivos' => (bool) env('RESPALDO_ARCHIVOS', true),

        // A quién avisar por email si un respaldo falla (vacío = no avisa)
        'avisar_a' => env('RESPALDO_AVISAR_A'),
    ],

    /*
    | Cola de envíos (mails). Si es true, el cron de Laravel procesa la cola
    | cada minuto y no hace falta dejar un "queue:work" corriendo aparte.
    */
    'cola_por_cron' => (bool) env('COLA_POR_CRON', true),

];
