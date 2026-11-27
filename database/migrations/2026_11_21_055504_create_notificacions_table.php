<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->constrained('usuarios');   // destinatario de la notificación //

            $table->string('titulo', 150);   // encabezado corto //
            $table->text('mensaje');         // cuerpo libre //
            $table->string('tipo', 50);      // ej. "postulacion", "oferta", "sistema" //
            $table->boolean('leida')->default(false);   // bandera de lectura //
            $table->dateTime('fecha');       // cuándo se generó //

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
