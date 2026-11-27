<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas_empleo', function (Blueprint $table) {
            // ¿Está actualmente destacada? (false por defecto) //
            $table->boolean('destacada')
                ->default(false)
                ->after('estado');

            // Cuándo expira el destacado (null = nunca fue destacada o ya expiró) //
            $table->dateTime('destacada_hasta')
                ->nullable()
                ->after('destacada');

            // Índice para que el orden por destacada sea rápido en listados //
            $table->index('destacada');
        });
    }

    public function down(): void
    {
        Schema::table('ofertas_empleo', function (Blueprint $table) {
            $table->dropIndex(['destacada']);
            $table->dropColumn(['destacada', 'destacada_hasta']);
        });
    }
};
