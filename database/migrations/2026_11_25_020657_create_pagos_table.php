<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            // Quién pagó: FK al usuario (empresa que destaca la oferta) //
            $table->foreignId('usuario_id')
                ->constrained('usuarios');

            // Qué se está pagando: FK a la oferta destacada //
            $table->foreignId('oferta_empleo_id')
                ->constrained('ofertas_empleo');

            // Datos del cobro //
            $table->decimal('monto', 10, 2);                      // 99.00 //
            $table->char('moneda', 3)->default('MXN');            // ISO 4217 //
            $table->string('concepto', 255);                      // "Destacar oferta X por 30 días" //
            $table->string('metodo_pago', 50)->default('tarjeta'); // siempre 'tarjeta' por ahora //

            // Estado del pago: pendiente, aprobado, rechazado, cancelado //
            $table->string('estado', 30)->default('pendiente');

            // ID de Stripe (PaymentIntent) — UNIQUE evita guardar el mismo pago 2 veces //
            $table->string('referencia_externa', 255)->unique();

            // Respuesta completa de Stripe en JSON (auditoría) //
            $table->json('metadata')->nullable();

            // Cuándo se aprobó el pago (null mientras esté pendiente) //
            $table->dateTime('fecha_pago')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índice para búsquedas rápidas por usuario y por estado //
            $table->index(['usuario_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
