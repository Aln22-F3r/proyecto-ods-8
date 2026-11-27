<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();   // identificador del usuario //

            $table->foreignId('rol_id')
                ->constrained('roles');   // rol asignado al usuario //

            $table->string('correo', 150)->unique();   // correo único //
            $table->string('password', 255);   // contraseña cifrada //

            $table->unsignedTinyInteger('nivel')->nullable();   // nivel opcional del usuario //

            $table->dateTime('fecha_registro');   // fecha de registro //
            $table->boolean('estado')->default(true);   // estado activo por defecto //

            $table->rememberToken();   // token para recordar sesión //
            $table->timestamps();   // fechas de creación y actualización //
            $table->softDeletes();   // fecha de eliminación lógica //
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');   // elimina la tabla //
    }
};
