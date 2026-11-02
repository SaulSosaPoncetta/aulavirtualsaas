<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla estándar de notificaciones de Laravel (campanita)
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_id', 'read_at']);
        });

        // Preferencia: recibir avisos por email
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notificar_email')->default(true)->after('terminos_aceptados_at');
        });

        // Marca de "ya se avisó a los alumnos" en el contenido
        foreach (['clases', 'tareas', 'evaluaciones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dateTime('notificado_at')->nullable();
            });

            // Lo que ya existe se da por avisado (para no mandar avisos viejos al instalar)
            DB::table($tabla)->update(['notificado_at' => Carbon::now('America/Argentina/Buenos_Aires')]);
        }
    }

    public function down(): void
    {
        foreach (['clases', 'tareas', 'evaluaciones'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $table) => $table->dropColumn('notificado_at'));
        }

        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('notificar_email'));

        Schema::dropIfExists('notifications');
    }
};
