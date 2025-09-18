<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('novedades', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion');
            $table->string('tipo');
            $table->unsignedBigInteger('lugar_id');
            $table->string('ubicacion_detallada');
            $table->unsignedBigInteger('usuario_id');
            $table->string('estado_novedad');
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_finalizacion')->nullable();
            $table->timestamps();

            $table->foreign('lugar_id')->references('id')->on('lugar')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('novedades');
    }
}; 