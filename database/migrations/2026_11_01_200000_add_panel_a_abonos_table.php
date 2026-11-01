<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            $table->string('panel_estado', 20)->nullable()->after('vence_el');   // último estado informado por el Panel
            $table->string('panel_plan', 60)->nullable()->after('panel_estado');
            $table->timestamp('panel_sync_at')->nullable()->after('panel_plan'); // "enviado_en" del último aviso aplicado
        });
    }

    public function down(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            $table->dropColumn(['panel_estado', 'panel_plan', 'panel_sync_at']);
        });
    }
};
