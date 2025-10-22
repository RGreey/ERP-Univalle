<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Lista única de standby (beneficiarios + externos SIEMPRE)
        Schema::create('subsidio_standby_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('convocatorias_subsidio')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('es_externo')->default(false);
            $table->boolean('activo')->default(true);
            // Preferencia estricta por día: 'caicedonia','sevilla','ninguno'
            $enum = ['caicedonia','sevilla','ninguno'];
            $table->enum('pref_lun', $enum)->default('ninguno');
            $table->enum('pref_mar', $enum)->default('ninguno');
            $table->enum('pref_mie', $enum)->default('ninguno');
            $table->enum('pref_jue', $enum)->default('ninguno');
            $table->enum('pref_vie', $enum)->default('ninguno');
            $table->timestamps();

            $table->unique(['convocatoria_id','user_id'], 'standby_registros_uq');
            $table->index(['convocatoria_id','activo','created_at'], 'standby_registros_fifo_idx');
        });

        // Ofertas (multi-ofertas en paralelo por batch)
        Schema::create('subsidio_standby_ofertas', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id');
            $table->foreignId('cupo_diario_id')->constrained('subsidio_cupos_diarios')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('estado', ['pendiente','aceptada','asignada','expirada','rechazada','cup_full'])->default('pendiente');
            $table->string('token', 64)->unique();
            $table->timestamp('enviado_en')->nullable();
            $table->timestamp('vence_en')->nullable();
            $table->timestamp('aceptada_en')->nullable();
            $table->enum('via', ['email','admin'])->default('email');
            $table->timestamps();

            $table->index(['cupo_diario_id','estado'], 'standby_ofertas_cupo_estado_idx');
            $table->index('batch_id', 'standby_ofertas_batch_idx');
            // Evitar múltiples "pendientes" al mismo user para el mismo cupo (enforcer en app; aquí index auxiliar)
            $table->index(['cupo_diario_id','user_id','estado'], 'standby_ofertas_no_dup_idx');
        });

        // Strikes (no_show y cancel_tardia)
        Schema::create('subsidio_strikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('tipo', ['no_show','cancel_tardia']);
            $table->date('fecha');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['user_id','fecha']);
        });

        // Bajas del subsidio (retiros)
        Schema::create('subsidio_bajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convocatoria_id')->constrained('convocatorias_subsidio')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('motivo', ['voluntaria','inasistencias','otro']);
            $table->text('detalle')->nullable();
            $table->string('evidencia_pdf_path')->nullable();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['convocatoria_id','user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsidio_bajas');
        Schema::dropIfExists('subsidio_strikes');
        Schema::dropIfExists('subsidio_standby_ofertas');
        Schema::dropIfExists('subsidio_standby_registros');
    }
};