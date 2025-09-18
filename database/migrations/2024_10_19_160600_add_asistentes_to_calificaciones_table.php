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
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->integer('asistentes')->nullable()->after('comentario'); // Agregar el campo de asistentes
        });
    }

    public function down()
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropColumn('asistentes')->nullable()->after('comentario'); // Eliminar el campo si es necesario
        });
    }
};
