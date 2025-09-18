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
        Schema::create('semanas_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades_mantenimiento')->onDelete('cascade');
            $table->integer('anio');
            $table->integer('mes'); // 1-12
            $table->integer('semana'); // 1-4 (4 semanas por mes)
            $table->boolean('ejecutado')->default(false); // Marcado con X
            $table->date('fecha_ejecucion')->nullable(); // Fecha específica de ejecución
            $table->text('observaciones')->nullable(); // Observaciones de la ejecución
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['actividad_id', 'anio', 'mes']);
            $table->unique(['actividad_id', 'anio', 'mes', 'semana']); // Evitar duplicados
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semanas_mantenimiento');
    }
};
