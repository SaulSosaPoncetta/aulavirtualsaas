<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Una entrega por alumno y por tarea (se puede reenviar hasta que se corrige)
        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('users')->cascadeOnDelete();
            $table->longText('texto')->nullable();
            $table->enum('estado', ['entregada', 'devuelta', 'corregida'])->default('entregada');
            $table->dateTime('entregada_at')->nullable();
            $table->decimal('nota', 5, 2)->nullable();
            $table->text('devolucion')->nullable();
            $table->dateTime('corregida_at')->nullable();
            $table->foreignId('corregida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tarea_id', 'alumno_id']);
            $table->index(['tarea_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas');
    }
};
