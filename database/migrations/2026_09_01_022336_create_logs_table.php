<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();   // identificador del registro //

            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();   // usuario que realizó la acción //

            $table->string('accion', 50);   // tipo de acción realizada //
            $table->text('descripcion')->nullable();   // detalles de la acción //
            $table->string('tabla_afectada', 100)->nullable();   // tabla donde ocurrió el cambio //
            $table->unsignedBigInteger('registro_id')->nullable();   // identificador del registro afectado //
            $table->dateTime('fecha');   // fecha en que ocurrió la acción //

            $table->timestamps();   // fechas de creación y actualización //
            $table->softDeletes();   // fecha de eliminación lógica //
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');   // elimina la tabla //
    }
};
