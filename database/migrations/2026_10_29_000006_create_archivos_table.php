<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Archivos adjuntos de tareas (consigna) y de entregas (trabajo del alumno)
        Schema::create('archivos', function (Blueprint $table) {
            $table->id();
            $table->morphs('archivable');               // archivable_type + archivable_id
            $table->string('path');                     // ruta en storage/app/private
            $table->string('nombre');                   // nombre original
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('tamano')->nullable(); // bytes
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // quién lo subió
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};
