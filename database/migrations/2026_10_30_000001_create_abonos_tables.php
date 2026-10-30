<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Precios. Unipersonal: precio mensual. Institucional: precio por docente por mes, por escala.
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['unipersonal', 'institucional']);
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 12, 2);
            $table->unsignedSmallInteger('docentes_desde')->nullable(); // escala institucional
            $table->unsignedSmallInteger('docentes_hasta')->nullable(); // null = sin tope
            $table->boolean('activo')->default(true);
            $table->unsignedTinyInteger('orden')->default(0);
            $table->timestamps();
        });

        // Estado del abono de cada docente (unipersonal) o institución
        Schema::create('abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('institucion_id')->nullable()->unique()->constrained('instituciones')->cascadeOnDelete();
            $table->enum('tipo', ['unipersonal', 'institucional']);
            $table->unsignedSmallInteger('cupo_docentes')->nullable(); // solo institucional
            $table->date('vence_el')->nullable();                      // null = nunca pagó
            $table->timestamps();
        });

        // Pagos (Mercado Pago, transferencia, efectivo...)
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abono_id')->constrained('abonos')->cascadeOnDelete();
            $table->enum('concepto', ['renovacion', 'ampliacion'])->default('renovacion');
            $table->enum('metodo', ['mercadopago', 'transferencia', 'efectivo', 'otro']);
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado', 'cancelado'])->default('pendiente');
            $table->unsignedTinyInteger('meses')->default(1);
            $table->unsignedSmallInteger('cupo_docentes')->nullable();
            $table->decimal('importe', 12, 2);
            $table->string('comprobante_path')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('mp_preference_id')->nullable();
            $table->string('mp_payment_id')->nullable()->index();
            $table->string('mp_status', 40)->nullable();
            $table->dateTime('aprobado_at')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete(); // superadmin que aprobó
            $table->timestamps();

            $table->index(['estado', 'metodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('abonos');
        Schema::dropIfExists('planes');
    }
};
