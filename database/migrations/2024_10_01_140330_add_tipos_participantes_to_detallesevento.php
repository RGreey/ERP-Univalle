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
            $table->boolean('certificacion')->after('diseñoPublicitario');
            $table->boolean('estudiantes');
            $table->boolean('profesores');
            $table->boolean('administrativos');
            $table->boolean('empresarios');
            $table->boolean('comunidad_general');
            $table->boolean('egresados');
            $table->boolean('invitados_externos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detallesevento', function (Blueprint $table) {
            $table->dropColumn('certificacion');
            $table->dropColumn('estudiantes');
            $table->dropColumn('profesores');
            $table->dropColumn('administrativos');
            $table->dropColumn('empresarios');
            $table->dropColumn('comunidad_general');
            $table->dropColumn('egresados');
            $table->dropColumn('invitados_externos');
        });
    }
};
