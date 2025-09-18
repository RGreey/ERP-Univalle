<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDesempenoMonitorTable extends Migration
{
    public function up()
    {
        Schema::create('desempeno_monitor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monitor_id');
            $table->unsignedBigInteger('convocatoria_id')->nullable();
            $table->string('periodo_academico')->nullable();
            $table->string('codigo_estudiantil')->nullable();
            $table->string('programa_academico')->nullable();
            $table->string('apellidos_estudiante')->nullable();
            $table->string('nombres_estudiante')->nullable();
            $table->string('modalidad_monitoria')->nullable();
            $table->string('dependencia')->nullable();
            $table->string('evaluador_identificacion')->nullable();
            $table->string('evaluador_apellidos')->nullable();
            $table->string('evaluador_nombres')->nullable();
            $table->string('evaluador_cargo')->nullable();
            $table->string('evaluador_dependencia')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->float('calidad_trabajo', 3, 1)->nullable();
            $table->float('sigue_instrucciones', 3, 1)->nullable();
            $table->float('responsable_actividad', 3, 1)->nullable();
            $table->float('iniciativa', 3, 1)->nullable();
            $table->float('cumplimiento_horario', 3, 1)->nullable();
            $table->float('relaciones_interpersonales', 3, 1)->nullable();
            $table->float('cooperacion', 3, 1)->nullable();
            $table->float('atencion_usuario', 3, 1)->nullable();
            $table->float('asume_compromisos', 3, 1)->nullable();
            $table->float('maneja_informacion', 3, 1)->nullable();
            $table->float('puntaje_total', 4, 2)->nullable();
            $table->text('sugerencias')->nullable();
            $table->date('fecha_evaluacion')->nullable();
            $table->string('firma_evaluador')->nullable();
            $table->string('firma_evaluado')->nullable();
            $table->timestamps();
            $table->foreign('monitor_id')->references('id')->on('monitor')->onDelete('cascade');
            $table->foreign('convocatoria_id')->references('id')->on('convocatorias')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('desempeno_monitor');
    }
}
