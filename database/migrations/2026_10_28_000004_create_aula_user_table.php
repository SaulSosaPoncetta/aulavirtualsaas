<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Miembros de cada aula: docentes (titular y co-docentes) y alumnos
        Schema::create('aula_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('rol', ['docente', 'alumno']);
            $table->enum('origen', ['creador', 'codigo', 'manual', 'excel', 'institucion'])->default('manual');
            $table->enum('estado', ['activo', 'baja'])->default('activo');
            $table->date('fecha_alta')->nullable();
            $table->date('fecha_baja')->nullable();
            $table->timestamps();

            $table->unique(['aula_id', 'user_id']);
            $table->index(['user_id', 'rol', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aula_user');
    }
};
