<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                   // nombre
            $table->string('apellido');
            $table->string('dni', 15)->nullable()->unique();          // se puede ingresar con DNI
            $table->string('email')->nullable()->unique();            // o con email
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('debe_cambiar_password')->default(false); // alta hecha por docente/institución
            $table->string('telefono', 40)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_acceso_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
