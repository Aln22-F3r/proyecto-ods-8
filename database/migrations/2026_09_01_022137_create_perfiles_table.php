<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles', function (Blueprint $table) {
            $table->id();

            // FK 1:1 con usuarios; ON DELETE CASCADE borra el perfil al borrar al usuario //
            $table->foreignId('usuario_id')
                ->unique()
                ->constrained('usuarios')
                ->cascadeOnDelete();

            $table->string('nombre', 100);                       // obligatorio, min 2 //
            $table->string('apellido', 100)->nullable();         // opcional //
            $table->string('telefono', 20)->nullable();          // solo dígitos //

            $table->string('profesion_oficio', 100)->nullable(); // ej. "Ing. Sistemas" //
            $table->text('descripcion')->nullable();             // bio libre //
            $table->unsignedInteger('experiencia')->nullable();  // años, 0-60 //
            $table->text('habilidades')->nullable();             // texto libre //
            $table->string('ciudad', 100)->nullable();           // residencia //
            $table->string('cv', 255)->nullable();               // ruta del PDF/DOC en disco //
            $table->string('foto', 255)->nullable();             // ruta de la imagen en disco //

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles');
    }
};
