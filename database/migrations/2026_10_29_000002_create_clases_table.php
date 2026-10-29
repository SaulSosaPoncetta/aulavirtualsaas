<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clases / publicaciones de material dentro de un aula
        Schema::create('clases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete(); // null = "General"
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();      // autor
            $table->string('titulo', 200);
            $table->longText('contenido')->nullable();
            $table->dateTime('fecha_publicacion')->nullable(); // null = publicada al guardar
            $table->boolean('visible')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['aula_id', 'unidad_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clases');
    }
};
