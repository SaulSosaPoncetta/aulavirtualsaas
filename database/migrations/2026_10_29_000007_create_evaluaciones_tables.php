<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Evaluaciones / cuestionarios online
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo', 200);
            $table->text('instrucciones')->nullable();
            $table->dateTime('fecha_publicacion')->nullable();          // desde cuándo se puede rendir
            $table->dateTime('fecha_cierre')->nullable();               // hasta cuándo (null = sin cierre)
            $table->unsignedSmallInteger('duracion_minutos')->nullable(); // null = sin límite de tiempo
            $table->unsignedTinyInteger('intentos_permitidos')->default(1);
            $table->enum('mostrar_resultados', ['al_finalizar', 'al_cerrar', 'nunca'])->default('al_finalizar');
            $table->boolean('mezclar_preguntas')->default(false);
            $table->decimal('puntaje_maximo', 5, 2)->default(10);       // escala de la nota
            $table->boolean('visible')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['aula_id', 'unidad_id']);
        });

        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones')->cascadeOnDelete();
            $table->enum('tipo', ['unica', 'multiple', 'verdadero_falso', 'texto']);
            $table->text('enunciado');
            $table->decimal('puntos', 5, 2)->default(1);
            $table->text('explicacion')->nullable(); // se muestra al alumno con los resultados
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained('preguntas')->cascadeOnDelete();
            $table->string('texto', 500);
            $table->boolean('es_correcta')->default(false);
            $table->unsignedTinyInteger('orden')->default(0);
            $table->timestamps();
        });

        // Cada vez que un alumno rinde
        Schema::create('intentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero')->default(1);
            $table->dateTime('iniciado_at');
            $table->dateTime('finalizado_at')->nullable();
            $table->enum('estado', ['en_curso', 'pendiente_revision', 'finalizado'])->default('en_curso');
            $table->decimal('puntaje', 6, 2)->nullable(); // puntos obtenidos
            $table->decimal('nota', 5, 2)->nullable();    // puntaje llevado a la escala de la evaluación
            $table->foreignId('corregido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['evaluacion_id', 'alumno_id', 'numero']);
        });

        Schema::create('respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intento_id')->constrained('intentos')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('preguntas')->cascadeOnDelete();
            $table->json('opciones_ids')->nullable(); // opciones elegidas
            $table->text('texto')->nullable();        // respuesta escrita
            $table->boolean('correcta')->nullable();  // null = falta corregir (texto)
            $table->decimal('puntos', 5, 2)->nullable();
            $table->text('comentario')->nullable();   // devolución del docente
            $table->timestamps();

            $table->unique(['intento_id', 'pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas');
        Schema::dropIfExists('intentos');
        Schema::dropIfExists('opciones');
        Schema::dropIfExists('preguntas');
        Schema::dropIfExists('evaluaciones');
    }
};
