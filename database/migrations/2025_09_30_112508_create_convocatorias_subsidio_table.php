<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConvocatoriasSubsidioTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('convocatorias_subsidio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedBigInteger('periodo_academico');
            $table->date('fecha_apertura');
            $table->date('fecha_cierre');
            $table->integer('cupos_caicedonia')->default(0);
            $table->integer('cupos_sevilla')->default(0);
            $table->enum('estado', ['activa', 'cerrada', 'borrador'])->default('borrador');
            $table->timestamps();

            $table->foreign('periodo_academico')
                ->references('id')
                ->on('periodoAcademico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('convocatorias_subsidio');
    }
}