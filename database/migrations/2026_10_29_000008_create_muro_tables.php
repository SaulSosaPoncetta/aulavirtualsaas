<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Avisos / publicaciones del muro del aula
        Schema::create('publicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('texto');
            $table->boolean('fijada')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['aula_id', 'fijada', 'created_at']);
        });

        Schema::create('comentarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publicacion_id')->constrained('publicaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('texto');
            $table->timestamps();
            $table->softDeletes();
        });

        // Permisos del muro por aula
        Schema::table('aulas', function (Blueprint $table) {
            $table->boolean('alumnos_publican')->default(false)->after('codigo_activo');
            $table->boolean('alumnos_comentan')->default(true)->after('alumnos_publican');
        });

        // Última vez que cada miembro miró el muro (para el contador de novedades)
        Schema::table('aula_user', function (Blueprint $table) {
            $table->dateTime('muro_visto_at')->nullable()->after('fecha_baja');
        });
    }

    public function down(): void
    {
        Schema::table('aula_user', function (Blueprint $table) {
            $table->dropColumn('muro_visto_at');
        });

        Schema::table('aulas', function (Blueprint $table) {
            $table->dropColumn(['alumnos_publican', 'alumnos_comentan']);
        });

        Schema::dropIfExists('comentarios');
        Schema::dropIfExists('publicaciones');
    }
};
