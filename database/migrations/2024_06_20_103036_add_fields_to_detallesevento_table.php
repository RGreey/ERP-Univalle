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
        Schema::table('detallesevento', function (Blueprint $table) {
            $table->boolean('presentacion_cultural');
            $table->boolean('estacion_bebidas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detallesevento', function (Blueprint $table) {
            $table->dropColumn('presentacion_cultural');
            $table->dropColumn('estacion_bebidas');
        });
    }
};
