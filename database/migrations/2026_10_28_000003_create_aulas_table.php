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
            $table->foreignId('institucion_id')->nullable()->constrained('instituciones')->nullOnDelete(); // null = aula de docente suelto
            $table->foreignId('docente_id')->constrained('users')->cascadeOnDelete();                       // titular / creador

            $table->string('materia');
            $table->string('curso')->nullable();      // ej: 3°
            $table->string('division')->nullable();   // ej: A
            $table->string('turno')->nullable();
            $table->string('escuela')->nullable();    // nombre libre si no pertenece a una institución
            $table->unsignedSmallInteger('ciclo_lectivo');
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->string('color', 7)->default('#0F766E');

            // Inscripción de alumnos por código
            $table->string('codigo_acceso', 10)->unique();
            $table->boolean('codigo_activo')->default(true);

            $table->enum('estado', ['activa', 'archivada'])->default('activa');
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
