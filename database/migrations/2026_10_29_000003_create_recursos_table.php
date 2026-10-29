<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Material adjunto a una clase: archivos, enlaces y videos
        Schema::create('recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_id')->constrained('clases')->cascadeOnDelete();
            $table->enum('tipo', ['archivo', 'enlace', 'video']);
            $table->string('titulo', 200)->nullable();
            $table->string('url', 1000)->nullable();          // enlace / video
            $table->string('archivo_path')->nullable();       // ruta en storage/app/private
            $table->string('archivo_nombre')->nullable();     // nombre original
            $table->string('archivo_mime', 120)->nullable();
            $table->unsignedBigInteger('archivo_tamano')->nullable(); // bytes
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos');
    }
};
