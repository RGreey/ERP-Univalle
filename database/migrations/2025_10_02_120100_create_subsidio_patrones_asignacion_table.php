<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subsidio_patrones_asignacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('convocatorias_subsidio')->cascadeOnDelete();
            $table->foreignId('postulacion_id')->constrained('subsidio_postulaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Días ISO de la semana (1..5 normalmente): ej [1,3,5]
            $table->json('dias_iso');
            // Sede preferente/resuelta para el patrón (si aplica)
            $table->string('sede', 32)->nullable();
            // Límite semanal asignado según prioridad (documentativo)
            $table->unsignedTinyInteger('max_semanal')->default(1);
            $table->timestamps();

            $table->unique(['convocatoria_id','user_id'], 'patron_unico_por_convocatoria_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsidio_patrones_asignacion');
    }
};