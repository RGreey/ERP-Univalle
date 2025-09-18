<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('evento', function (Blueprint $table) {
            // Aseguramos que la columna se cree antes de intentar aplicar la clave foránea.
            $table->unsignedBigInteger('programadependenciasecundario')->nullable()->after('programadependencia');
            
            // Luego añadimos la clave foránea
            $table->foreign('programadependenciasecundario')
                ->references('id')
                ->on('programadependencia')
                ->onDelete('set null'); // Si se elimina la dependencia secundaria, el campo se establecerá en null
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('evento', function (Blueprint $table) {
            // Eliminamos la relación y la columna
            $table->dropForeign(['programadependenciasecundario']);
            $table->dropColumn('programadependenciasecundario');
        });
    }
};
