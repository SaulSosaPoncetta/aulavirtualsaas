<?php

/*
|--------------------------------------------------------------------------
| Integraciones externas del Aula Virtual
|--------------------------------------------------------------------------
| GestiónAula: login de docentes, designaciones y envío de notas.
| SGI: login de alumnos, instituciones y alumnos por curso (por DNI).
*/

return [

    'gestionaula' => [
        'url'        => rtrim(env('GESTIONAULA_URL', ''), '/'),
        'prefix'     => env('GESTIONAULA_API_PREFIX', '/api/app'),
        'token'      => env('GESTIONAULA_TOKEN'),
        'endpoints'  => [
            'login'          => '/login',
            'logout'         => '/logout',
            'usuario'        => '/user',
            'designaciones'  => '/aulavirtual/designaciones',
            'enviar_notas'   => '/aulavirtual/notas',
        ],
    ],

    'sgi' => [
        'url'        => rtrim(env('SGI_URL', ''), '/'),
        'prefix'     => env('SGI_API_PREFIX', '/api/aulavirtual'),
        'token'      => env('SGI_TOKEN'),
        'endpoints'  => [
            'login_alumno'   => '/login-alumno',
            'instituciones'  => '/instituciones',
            'alumnos_curso'  => '/cursos/{curso}/alumnos',
            'alumno_dni'     => '/alumnos/{dni}',
        ],
    ],

    'timeout' => (int) env('INTEGRACION_TIMEOUT', 15),

];
