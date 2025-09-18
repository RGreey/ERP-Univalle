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
        Schema::create('actividades_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->string('actividad', 255); // Nombre de la actividad
            $table->text('descripcion')->nullable(); // Descripción detallada
            $table->enum('frecuencia', ['anual', 'trimestral', 'cuatrimestral', 'mensual', 'cuando_se_requiera']);
            $table->date('fecha_inicio'); // Fecha de inicio del rango o fecha única
            $table->date('fecha_final'); // Fecha final del rango o fecha única
            $table->boolean('realizado')->default(false); // Sí/No (marcado con X)
            $table->string('proveedor', 255)->nullable(); // Si está vacío = servicios generales
            $table->string('responsable', 255); // Quien ejecuta la actividad
            $table->integer('orden')->default(0); // Para mantener el orden de las 23 actividades
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['frecuencia', 'realizado']);
            $table->index(['fecha_inicio', 'fecha_final']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividades_mantenimiento');
    }
};
