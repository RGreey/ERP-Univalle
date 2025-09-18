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
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->dropColumn('cupos');

            // Agregar los nuevos campos para las horas
            $table->integer('horas_administrativo')->nullable()->after('fechaEntrevistas');
            $table->integer('horas_docencia')->nullable()->after('horas_administrativo');
            $table->integer('horas_investigacion')->nullable()->after('horas_docencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->json('cupos')->nullable()->after('fechaEntrevistas');

            // Eliminar los nuevos campos
            $table->dropColumn('horas_administrativo');
            $table->dropColumn('horas_docencia');
            $table->dropColumn('horas_investigacion');
        });
    }
};
