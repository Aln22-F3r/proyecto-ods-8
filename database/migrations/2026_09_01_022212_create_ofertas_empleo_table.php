<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ofertas_empleo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('categoria_id');
            $table->foreign('categoria_id')->references('id')->on('categorias');
            $table->string('titulo', 150);
            $table->string('empresa', 150);
            $table->text('descripcion');
            $table->string('ubicacion', 150);
            $table->decimal('salario', 10, 2);
            $table->string('tipo_empleo', 50);
            $table->dateTime('fecha_publicacion');
            $table->string('estado', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ofertas_empleo');
    }
};
