<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('terminos_aceptados_at')->nullable()->after('debe_cambiar_password');
        });

        // Los usuarios que ya existían quedan con el email verificado (no se les pide verificar)
        DB::table('users')
            ->whereNotNull('email')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => Carbon::now('America/Argentina/Buenos_Aires')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('terminos_aceptados_at');
        });
    }
};
