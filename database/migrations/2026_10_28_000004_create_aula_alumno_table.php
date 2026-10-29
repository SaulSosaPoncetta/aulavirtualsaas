<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Inscripciones de alumnos a aulas
        Schema::create('aula_alumno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('users')->cascadeOnDelete();
            $table->enum('origen', ['sgi', 'codigo', 'manual', 'excel'])->default('manual');
            $table->enum('estado', ['activo', 'baja'])->default('activo');
            $table->date('fecha_alta')->nullable();
            $table->date('fecha_baja')->nullable();
            $table->timestamps();

            $table->unique(['aula_id', 'alumno_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aula_alumno');
    }
};
