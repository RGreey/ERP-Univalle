<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
        {
            Schema::create('asistencias_monitoria', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('monitor_id');
                $table->unsignedTinyInteger('mes');
                $table->unsignedSmallInteger('anio');
                $table->string('asistencia_path');
                $table->timestamps();

                $table->foreign('monitor_id')->references('id')->on('monitor')->onDelete('cascade');
            });
        }

    public function down()
    {
        Schema::dropIfExists('asistencias_monitoria');
    }
};
