<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Link a encuesta y (opcional) interruptor de recepción
        Schema::table('convocatorias_subsidio', function (Blueprint $table) {
            if (!Schema::hasColumn('convocatorias_subsidio', 'encuesta_id')) {
                $table->foreignId('encuesta_id')->nullable()->after('estado')->constrained('subsidio_encuestas')->nullOnDelete();
            }
            if (!Schema::hasColumn('convocatorias_subsidio', 'recepcion_habilitada')) {
                $table->boolean('recepcion_habilitada')->default(true)->after('encuesta_id');
            }
            $table->index(['fecha_apertura','fecha_cierre'], 'idx_conv_subsidio_fechas');
        });

        // Postulaciones
        Schema::create('subsidio_postulaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('convocatoria_id')->constrained('convocatorias_subsidio')->cascadeOnDelete();
            $table->enum('sede', ['Caicedonia','Sevilla']);
            $table->enum('estado', ['enviada','evaluada','beneficiario','rechazada','anulada'])->default('enviada');
            $table->decimal('puntaje_total', 10, 2)->nullable();
            $table->unsignedInteger('posicion')->nullable();
            $table->timestamps();

            $table->unique(['user_id','convocatoria_id']);
            $table->index(['convocatoria_id','sede']);
        });

        // Respuestas
        Schema::create('subsidio_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulacion_id')->constrained('subsidio_postulaciones')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('subsidio_preguntas')->cascadeOnDelete();

            $table->text('respuesta_texto')->nullable();
            $table->decimal('respuesta_numero', 12, 2)->nullable();
            $table->date('respuesta_fecha')->nullable();
            $table->foreignId('opcion_id')->nullable()->constrained('subsidio_opciones')->nullOnDelete(); // selección única
            $table->json('opcion_ids')->nullable(); // selección múltiple

            $table->timestamps();

            $table->unique(['postulacion_id','pregunta_id']);
            $table->index('pregunta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsidio_respuestas');
        Schema::dropIfExists('subsidio_postulaciones');

        Schema::table('convocatorias_subsidio', function (Blueprint $table) {
            if (Schema::hasColumn('convocatorias_subsidio', 'encuesta_id')) {
                $table->dropConstrainedForeignId('encuesta_id');
            }
            if (Schema::hasColumn('convocatorias_subsidio', 'recepcion_habilitada')) {
                $table->dropColumn('recepcion_habilitada');
            }
            $table->dropIndex('idx_conv_subsidio_fechas');
        });
    }
};