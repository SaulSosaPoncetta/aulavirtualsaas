<?php

use App\Services\Notificador;
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

Schedule::command('aula:publicaciones')->everyTenMinutes()->withoutOverlapping();
Schedule::command('aula:recordatorios')->dailyAt('08:00')->timezone('America/Argentina/Buenos_Aires');

// Limpieza: notificaciones leídas de más de 90 días
Schedule::call(function () {
    \Illuminate\Support\Facades\DB::table('notifications')
        ->whereNotNull('read_at')
        ->where('created_at', '<', now()->subDays(90))
        ->delete();
})->weekly();
