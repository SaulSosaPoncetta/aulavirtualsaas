<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Períodos de evaluación de cada aula (trimestres, cuatrimestres...)
        Schema::create('periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->string('nombre', 60);
            $table->date('desde');
            $table->date('hasta');
            $table->unsignedTinyInteger('orden')->default(1);
            $table->timestamps();
        });

        // Columnas de nota cargadas a mano (concepto, oral, trabajo en clase...)
        Schema::create('columnas_libreta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->nullOnDelete();
            $table->string('nombre', 80);
            $table->decimal('puntaje_maximo', 5, 2)->default(10);
            $table->decimal('peso', 4, 2)->default(1);
            $table->date('fecha')->nullable();
            $table->timestamps();
        });

        Schema::create('notas_manuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('columna_id')->constrained('columnas_libreta')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('nota', 5, 2)->nullable();
            $table->string('observacion', 300)->nullable();
            $table->timestamps();

            $table->unique(['columna_id', 'alumno_id']);
        });

        // Nota de cierre de cada período (la que decide el docente)
        Schema::create('cierres_periodo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('nota', 5, 2)->nullable();
            $table->string('observacion', 500)->nullable();
            $table->timestamps();

            $table->unique(['periodo_id', 'alumno_id']);
        });

        // Peso de cada tarea y evaluación en el promedio (1 = normal, 2 = vale doble)
        foreach (['tareas', 'evaluaciones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->decimal('peso', 4, 2)->default(1);
            });
        }
    }

    public function down(): void
    {
        foreach (['tareas', 'evaluaciones'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $table) => $table->dropColumn('peso'));
        }

        Schema::dropIfExists('cierres_periodo');
        Schema::dropIfExists('notas_manuales');
        Schema::dropIfExists('columnas_libreta');
        Schema::dropIfExists('periodos');
    }
};
