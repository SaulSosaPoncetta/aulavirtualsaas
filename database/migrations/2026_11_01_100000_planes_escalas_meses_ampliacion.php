<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Escalas de precio por cantidad (ej: 1-5 docentes a un precio, 6-20 a otro)
        Schema::create('plan_escalas', function (Blueprint $table) {
            $table->id('id_escala');
            $table->foreignId('id_plan')->constrained('planes', 'id_plan')->cascadeOnDelete();
            $table->unsignedInteger('desde');
            $table->unsignedInteger('hasta')->nullable(); // null = sin tope
            $table->decimal('precio', 12, 2);              // precio por unidad dentro de la escala
            $table->timestamps();
        });

        Schema::table('planes', function (Blueprint $table) {
            // Ej: "1,3,6,12". Si está cargado, el precio del plan es POR MES y el cliente elige cuántos meses pagar.
            $table->string('meses_permitidos', 50)->nullable()->after('duracion_meses');
        });

        // Cada pago recuerda qué se está pagando: se aplica a la suscripción recién cuando se aprueba
        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignId('id_plan')->nullable()->after('id_suscripcion')->constrained('planes', 'id_plan')->nullOnDelete();
            $table->string('concepto', 20)->default('renovacion')->after('medio_pago'); // renovacion | ampliacion
            $table->unsignedSmallInteger('meses')->nullable()->after('concepto');
            $table->unsignedInteger('cantidad')->nullable()->after('meses');           // cupo contratado (ej: docentes)
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_plan');
            $table->dropColumn(['concepto', 'meses', 'cantidad']);
        });

        Schema::table('planes', function (Blueprint $table) {
            $table->dropColumn('meses_permitidos');
        });

        Schema::dropIfExists('plan_escalas');
    }
};
