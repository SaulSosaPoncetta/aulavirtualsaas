<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Invitaciones a co-docentes
        Schema::create('aula_invitaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->foreignId('invitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['pendiente', 'aceptada', 'rechazada', 'cancelada'])->default('pendiente');
            $table->dateTime('vence_at');
            $table->dateTime('respondida_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aula_invitaciones');
    }
};
