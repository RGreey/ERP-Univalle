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
        Schema::table('evento', function (Blueprint $table) {
            // Primero quitamos las claves foráneas si existen
            $table->dropForeign(['programadependencia']);
            $table->dropForeign(['programadependenciasecundario']);
            $table->dropForeign(['programadependenciaterciaria']);

            // Luego eliminamos las columnas
            $table->dropColumn('programadependencia');
            $table->dropColumn('programadependenciasecundario');
            $table->dropColumn('programadependenciaterciaria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evento', function (Blueprint $table) {
            // Restauramos las columnas (si alguna vez necesitas revertir)
            $table->unsignedBigInteger('programadependencia');
            $table->unsignedBigInteger('programadependenciasecundario')->nullable();
            $table->unsignedBigInteger('programadependenciaterciaria')->nullable();

            // Restauramos las claves foráneas
            $table->foreign('programadependencia')->references('id')->on('programadependencia');
            $table->foreign('programadependenciasecundario')->references('id')->on('programadependencia')->onDelete('set null');
            $table->foreign('programadependenciaterciaria')->references('id')->on('programadependencia')->onDelete('set null');
        });
    }
};