<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfiles', function (Blueprint $table) {
            $table->string('foto', 255)->nullable()->after('cv');
        });
    }

    public function down(): void
    {
        Schema::table('perfiles', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
