<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lista_deseos', function (Blueprint $table) {
            $table->id();   // identificador del registro //

            $table->foreignId('usuario_id')
                ->constrained('usuarios');   // usuario propietario //

            $table->foreignId('oferta_empleo_id')
                ->constrained('ofertas_empleo');   // oferta guardada //

            $table->dateTime('fecha_agregado');   // fecha en que se guardó //

            $table->unique(['usuario_id', 'oferta_empleo_id']);   // evita ofertas duplicadas por usuario //

            $table->timestamps();   // fechas de creación y actualización //
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lista_deseos');   // elimina la tabla //
    }
};
