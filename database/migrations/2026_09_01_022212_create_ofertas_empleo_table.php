<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ofertas_empleo', function (Blueprint $table) {
            $table->id();   // identificador de la oferta //

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();   // usuario que publica la oferta //

            $table->foreignId('categoria_id')
                ->constrained('categorias');   // categoría de la oferta //

            $table->string('titulo', 150);   // título de la oferta //
            $table->text('descripcion');   // descripción del empleo //
            $table->string('ubicacion', 150);   // ubicación del empleo //
            $table->decimal('salario', 10, 2)->nullable();   // salario opcional //
            $table->string('tipo_empleo', 50);   // modalidad o tipo de empleo //
            $table->dateTime('fecha_publicacion');   // fecha de publicación //
            $table->string('estado', 30);   // estado de la oferta //
            $table->string('logo', 255)->nullable();   // logotipo opcional //

            $table->timestamps();   // fechas de creación y actualización //
            $table->softDeletes();   // fecha de eliminación lógica //
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ofertas_empleo');   // elimina la tabla //
    }
};
