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
        Schema::dropIfExists('evidencias_mantenimiento');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('evidencias_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->enum('sede', ['MI', 'VC', 'LI', 'Nodo'])->comment('Sede donde se realizó el mantenimiento');
            $table->string('titulo', 255)->comment('Título descriptivo de la evidencia');
            $table->text('descripcion')->comment('Descripción detallada de las actividades realizadas');
            $table->unsignedTinyInteger('mes')->comment('Mes del mantenimiento (1-12)');
            $table->unsignedInteger('anio')->comment('Año del mantenimiento');
            $table->string('archivo_pdf')->nullable()->comment('Ruta del archivo PDF con las evidencias');
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente')->comment('Estado de la evidencia');
            $table->unsignedBigInteger('usuario_id')->comment('ID del usuario que subió la evidencia');
            $table->timestamp('fecha_creacion')->useCurrent()->comment('Fecha de creación de la evidencia');
            $table->timestamp('fecha_aprobacion')->nullable()->comment('Fecha de aprobación o rechazo');
            $table->unsignedBigInteger('aprobado_por')->nullable()->comment('ID del usuario que aprobó/rechazó');
            $table->timestamps();

            // Índices
            $table->index(['sede', 'mes', 'anio'], 'idx_sede_mes_anio');
            $table->index('estado', 'idx_estado');
            $table->index('usuario_id', 'idx_usuario');
            $table->index('fecha_creacion', 'idx_fecha_creacion');

            // Clave única para evitar duplicados por sede/mes/año
            $table->unique(['sede', 'mes', 'anio'], 'unique_sede_mes_anio');

            // Claves foráneas
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('aprobado_por')->references('id')->on('users')->onDelete('set null');
        });
    }
};
