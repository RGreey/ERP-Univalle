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
        Schema::table('postulados', function (Blueprint $table) {
            $table->dateTime('entrevista_fecha')->nullable();
            $table->string('entrevista_medio')->nullable(); // presencial o virtual
            $table->string('entrevista_link')->nullable(); // solo si es virtual
            $table->string('entrevista_lugar')->nullable(); // solo si es presencial
            $table->text('concepto_entrevista')->nullable();
            $table->string('entrevistador')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postulados', function (Blueprint $table) {
            $table->dropColumn([
                'entrevista_fecha',
                'entrevista_medio',
                'entrevista_link',
                'entrevista_lugar',
                'concepto_entrevista',
                'entrevistador',
            ]);
        });
    }
};
