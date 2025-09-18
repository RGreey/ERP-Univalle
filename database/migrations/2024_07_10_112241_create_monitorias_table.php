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
        Schema::create('monitorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('convocatoria')->constrained('convocatorias');
            $table->foreignId('programadependencia')->constrained('programadependencia');
            $table->integer('vacante');
            $table->integer('intensidad');
            $table->enum('horario', ['diurno', 'nocturno', 'mixto']);
            $table->text('requisitos');
            $table->enum('modalidad', ['administrativo', 'docencia','investigacion']);
            $table->enum('estado', ['aprobado', 'pendiente', 'rechazado'])->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitorias');
    }
};
