<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('users')->cascadeOnDelete();

            // Vínculos externos
            $table->unsignedBigInteger('gestionaula_designacion_id')->nullable()->unique();
            $table->unsignedBigInteger('sgi_curso_id')->nullable()->index();

            // Datos del aula
            $table->string('materia');
            $table->string('curso')->nullable();      // ej: 3°
            $table->string('division')->nullable();   // ej: A
            $table->string('turno')->nullable();
            $table->unsignedSmallInteger('ciclo_lectivo');
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();

            // Inscripción por código (escuelas sin SGI o alumnos extra)
            $table->string('codigo_acceso', 10)->unique();
            $table->boolean('codigo_activo')->default(true);

            $table->enum('estado', ['activa', 'archivada'])->default('activa');
            $table->timestamp('sincronizado_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['docente_id', 'ciclo_lectivo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aulas');
    }
};
