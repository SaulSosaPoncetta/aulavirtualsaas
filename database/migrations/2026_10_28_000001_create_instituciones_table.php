<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instituciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sgi_id')->nullable()->unique(); // null = institución sin SGI
            $table->string('nombre');
            $table->string('cue', 20)->nullable();
            $table->string('nivel')->nullable();     // inicial, primaria, secundaria, técnica, terciario, universitario
            $table->string('localidad')->nullable();
            $table->string('logo')->nullable();
            $table->enum('origen', ['sgi', 'manual'])->default('manual');
            $table->boolean('activo')->default(true);
            $table->timestamp('sincronizado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instituciones');
    }
};
