<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete(); // null = "General"
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();      // autor
            $table->string('titulo', 200);
            $table->longText('consigna')->nullable();
            $table->dateTime('fecha_publicacion')->nullable(); // null = publicada al guardar
            $table->dateTime('fecha_limite')->nullable();      // null = sin fecha límite
            $table->boolean('acepta_tardias')->default(true);
            $table->decimal('puntaje_maximo', 5, 2)->default(10);
            $table->boolean('visible')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['aula_id', 'unidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
