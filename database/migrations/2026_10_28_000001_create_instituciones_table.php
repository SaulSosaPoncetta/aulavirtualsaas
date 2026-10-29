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
            $table->string('nombre');
            $table->string('cue', 20)->nullable();
            $table->string('nivel')->nullable();       // inicial, primaria, secundaria, técnica, terciario, universitario
            $table->string('localidad')->nullable();
            $table->string('provincia')->default('Buenos Aires');
            $table->string('telefono', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->string('codigo_invitacion', 10)->unique(); // para que docentes se sumen a la institución
            $table->foreignId('creada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instituciones');
    }
};
