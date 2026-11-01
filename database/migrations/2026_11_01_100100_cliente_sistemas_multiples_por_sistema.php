<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un mismo cliente (mismo email) puede tener más de una cuenta en un sistema
 * (ej: un docente que además administra una institución en Aula Virtual).
 * Lo que identifica la cuenta es la referencia_externa dentro de cada sistema.
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL necesita un índice propio en id_cliente antes de poder quitar el único compuesto
        Schema::table('cliente_sistemas', function (Blueprint $table) {
            $table->index('id_cliente', 'cliente_sistemas_id_cliente_index');
        });

        Schema::table('cliente_sistemas', function (Blueprint $table) {
            $table->dropUnique(['id_cliente', 'id_sistema']);
            $table->unique(['id_sistema', 'referencia_externa'], 'cliente_sistemas_sistema_referencia_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cliente_sistemas', function (Blueprint $table) {
            $table->dropUnique('cliente_sistemas_sistema_referencia_unique');
            $table->unique(['id_cliente', 'id_sistema']);
        });

        Schema::table('cliente_sistemas', function (Blueprint $table) {
            $table->dropIndex('cliente_sistemas_id_cliente_index');
        });
    }
};
