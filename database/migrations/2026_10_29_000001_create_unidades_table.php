<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unidades / módulos / temas en que se organiza el aula
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->index(['aula_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};
