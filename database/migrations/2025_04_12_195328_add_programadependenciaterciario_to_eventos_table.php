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
            // Agregar la columna nueva
            $table->unsignedBigInteger('programadependenciaterciaria')->nullable()->after('programadependenciasecundario');
            
            // Clave foránea
            $table->foreign('programadependenciaterciaria')
                ->references('id')
                ->on('programadependencia')
                ->onDelete('set null'); // Si se elimina, se pone en null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evento', function (Blueprint $table) {
            $table->dropForeign(['programadependenciaterciaria']);
            $table->dropColumn('programadependenciaterciaria');
        });
    }
};
