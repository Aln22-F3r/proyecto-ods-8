<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();   // identificador de la categoría //

            $table->string('nombre', 100)->unique();   // nombre único de la categoría //
            $table->string('descripcion', 255)->nullable();   // descripción opcional //

            $table->timestamps();   // fechas de creación y actualización //
            $table->softDeletes();   // fecha de eliminación lógica //
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');   // elimina la tabla //
    }
};
