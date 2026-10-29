<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un docente puede trabajar en varias instituciones; un alumno normalmente en una
        Schema::create('institucion_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institucion_id')->constrained('instituciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('rol', ['admin', 'docente', 'alumno']);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['institucion_id', 'user_id', 'rol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institucion_user');
    }
};
