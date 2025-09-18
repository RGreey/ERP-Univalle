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
        Schema::create('seguimiento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monitor'); // Relación con el monitor
            $table->date('fecha_monitoria'); // Fecha de la actividad
            $table->time('hora_ingreso'); // Hora de ingreso
            $table->time('hora_salida'); // Hora de salida
            $table->string('total_horas'); // Total de horas calculadas
            $table->text('actividad_realizada'); // Descripción de la actividad
            $table->timestamps();
    
            // Definir la relación de clave foránea
            $table->foreign('monitor')->references('id')->on('monitor')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguimiento');
    }
};
