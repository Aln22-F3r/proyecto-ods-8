<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postulaciones', function (Blueprint $table) {
            $table->id();

            // FK al candidato que se postuló (bloquea force delete si tiene postulaciones) //
            $table->foreignId('usuario_id')
                ->constrained('usuarios');

            // FK a la oferta (bloquea force delete de la oferta si tiene postulaciones) //
            $table->foreignId('oferta_empleo_id')
                ->constrained('ofertas_empleo');

            $table->string('cv', 255);                 // snapshot del CV al momento de postularse //

            $table->dateTime('fecha_postulacion');     // la pone el sistema, no el cliente //

            $table->string('estado', 30)->default('Pendiente');   // Pendiente, En revisión, Aceptada, Rechazada, Retirada //

            $table->text('comentario')->nullable();    // mensaje del candidato o nota de la empresa //

            // Evita que un candidato se postule 2 veces a la misma oferta //
            $table->unique(
                ['usuario_id', 'oferta_empleo_id'],
                'postulaciones_usuario_oferta_unique'
            );

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulaciones');
    }
};
