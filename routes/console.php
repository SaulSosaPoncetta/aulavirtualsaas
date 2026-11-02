<?php

use App\Services\Notificador;
use App\Services\RespaldoService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ===================== Aula Virtual =====================

Artisan::command('aula:publicaciones', function (Notificador $n) {
    $cant = $n->revisarProgramados();
    $this->info("Contenidos avisados: {$cant}");
})->purpose('Avisa a los alumnos del contenido programado que ya se publicó y de las evaluaciones listas');

Artisan::command('aula:recordatorios', function (Notificador $n) {
    $c = $n->recordatorios();
    $this->info("Recordatorios: {$c['tareas']} de tareas, {$c['evaluaciones']} de evaluaciones, {$c['abonos']} de abonos");
})->purpose('Recordatorios diarios: tareas y evaluaciones por vencer, abonos por vencer');

Artisan::command('aula:respaldo', function (RespaldoService $respaldos) {
    $this->info('Creando respaldo...');
    try {
        $r = $respaldos->crear();
    } catch (\Throwable $e) {
        $respaldos->avisarFalla($e);
        $this->error('Falló: ' . $e->getMessage());

        return 1;
    }
    $this->info("Listo: {$r['nombre']} (" . round($r['tamano'] / 1048576, 1) . " MB, {$r['archivos']} archivos). Borrados por antigüedad: {$r['borrados']}");
})->purpose('Respaldo de la base de datos y los archivos subidos (storage/app/respaldos)');

Artisan::command('aula:probar-correo {email}', function (string $email) {
    try {
        \Illuminate\Support\Facades\Mail::raw('¡Funciona! Correo de prueba del Aula Virtual.', fn ($m) => $m->to($email)->subject('Correo de prueba — ' . config('app.name')));
    } catch (\Throwable $e) {
        $this->error('No se pudo enviar: ' . $e->getMessage());

        return 1;
    }
    $this->info("Enviado a {$email}. Revisá también spam.");
})->purpose('Manda un correo de prueba en el momento para verificar el SMTP');

Schedule::command('aula:publicaciones')->everyTenMinutes()->withoutOverlapping();
Schedule::command('aula:recordatorios')->dailyAt('08:00')->timezone('America/Argentina/Buenos_Aires');

// Limpieza: notificaciones leídas de más de 90 días
Schedule::call(function () {
    \Illuminate\Support\Facades\DB::table('notifications')
        ->whereNotNull('read_at')
        ->where('created_at', '<', now()->subDays(90))
        ->delete();
})->weekly();

// Respaldo diario
Schedule::command('aula:respaldo')->dailyAt('03:30')->timezone('America/Argentina/Buenos_Aires')->withoutOverlapping(120);

// Cola de envíos procesada desde el cron (no hace falta dejar queue:work corriendo)
if (config('sistema.cola_por_cron')) {
    Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=55')
        ->everyMinute()->withoutOverlapping(5)->runInBackground();
}

// Envíos fallidos de más de 30 días
Schedule::command('queue:prune-failed --hours=720')->weekly();
