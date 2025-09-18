<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->integer('horas_investigacion')->after('horas_docencia')->default(0);
        });
    }

    public function down()
    {
        Schema::table('convocatorias', function (Blueprint $table) {
            $table->dropColumn('horas_investigacion');
        });
    }
}; 