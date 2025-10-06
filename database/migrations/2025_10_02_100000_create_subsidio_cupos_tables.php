<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Cupo diario por sede y fecha (derivado de la convocatoria)
        Schema::create('subsidio_cupos_diarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('convocatorias_subsidio')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('sede', ['caicedonia','sevilla']);
            $table->unsignedInteger('capacidad');   // cupos ofertados para ese día/sede
            $table->unsignedInteger('asignados')->default(0); // contador rápido
            $table->timestamps();

            $table->unique(['convocatoria_id','fecha','sede'], 'subsidio_cupos_diarios_uq');
            $table->index(['fecha','sede']);
        });

        // Asignación de cupo a un estudiante para un día/sede
        Schema::create('subsidio_cupo_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupo_diario_id')->constrained('subsidio_cupos_diarios')->cascadeOnDelete();
            $table->foreignId('postulacion_id')->constrained('subsidio_postulaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('estado', ['asignado','consumido','cancelado','ausente','justificado'])->default('asignado');
            $table->string('qr_token', 64)->nullable(); // para check-in con QR/código
            $table->timestamp('asignado_en')->nullable();
            $table->timestamp('consumido_en')->nullable();
            $table->timestamp('cancelado_en')->nullable();
            $table->timestamps();

            $table->unique(['cupo_diario_id','user_id'], 'subsidio_cupo_asig_unq_dia_user'); // 1 por día/sede
            $table->index(['user_id','estado']);
        });

        // Bitácora de cambios (estado/prioridad) si aún no existe
        if (! Schema::hasTable('subsidio_postulacion_logs')) {
            Schema::create('subsidio_postulacion_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('postulacion_id')->constrained('subsidio_postulaciones')->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('campo', 64); // estado | prioridad_final | asignacion | asistencia
                $table->text('valor_anterior')->nullable();
                $table->text('valor_nuevo')->nullable();
                $table->text('motivo')->nullable();
                $table->timestamps();
                $table->index(['postulacion_id','campo']);
            });
        }

        // Parámetros (opcional, para pesos/ventanas/horarios)
        if (! Schema::hasTable('subsidio_parametros')) {
            Schema::create('subsidio_parametros', function (Blueprint $table) {
                $table->id();
                $table->string('clave')->unique();
                $table->json('valor');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subsidio_cupo_asignaciones');
        Schema::dropIfExists('subsidio_cupos_diarios');
        // No bajamos logs/parametros por si ya estaban en uso
    }
};