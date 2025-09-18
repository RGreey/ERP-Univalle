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
        Schema::create('documentos_monitor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained('monitor')->onDelete('cascade');
            $table->enum('tipo_documento', ['seguimiento', 'asistencia', 'evaluacion_desempeno']);
            $table->integer('mes')->nullable(); // Para seguimiento y asistencia
            $table->integer('anio')->nullable(); // Para seguimiento y asistencia
            $table->string('ruta_archivo')->nullable(); // Para archivos físicos (asistencia)
            $table->text('parametros_generacion')->nullable(); // JSON con parámetros para regenerar PDFs
            $table->enum('estado', ['generado', 'firmado', 'pendiente'])->default('generado');
            $table->timestamp('fecha_generacion')->useCurrent();
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['monitor_id', 'tipo_documento']);
            $table->index(['monitor_id', 'mes', 'anio']);
            $table->unique(['monitor_id', 'tipo_documento', 'mes', 'anio'], 'unique_documento_monitor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_monitor');
    }
};
