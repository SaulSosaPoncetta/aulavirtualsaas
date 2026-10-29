<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registro de cada intercambio con GestiónAula y el SGI (para auditar y reintentar)
        Schema::create('sincronizaciones', function (Blueprint $table) {
            $table->id();
            $table->enum('sistema', ['gestionaula', 'sgi']);
            $table->string('tipo');                 // designaciones, alumnos, notas, login...
            $table->enum('direccion', ['entrada', 'salida']);
            $table->foreignId('aula_id')->nullable()->constrained('aulas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['ok', 'error', 'pendiente'])->default('pendiente');
            $table->unsignedSmallInteger('codigo_http')->nullable();
            $table->text('mensaje')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamps();

            $table->index(['sistema', 'tipo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizaciones');
    }
};
