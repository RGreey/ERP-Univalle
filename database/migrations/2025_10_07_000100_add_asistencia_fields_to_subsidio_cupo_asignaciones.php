<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subsidio_cupo_asignaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('subsidio_cupo_asignaciones', 'asistencia_estado')) {
                // pendiente, asistio, cancelado, no_show, reemplazo
                $table->string('asistencia_estado', 20)->default('pendiente')->after('estado');
            }
            if (!Schema::hasColumn('subsidio_cupo_asignaciones', 'cancelada_en')) {
                $table->timestamp('cancelada_en')->nullable()->after('asignado_en');
                $table->unsignedBigInteger('cancelada_por_user_id')->nullable()->after('cancelada_en');
                $table->string('cancelacion_origen', 20)->nullable()->after('cancelada_por_user_id'); // estudiante|restaurante
                $table->text('cancelacion_motivo')->nullable()->after('cancelacion_origen');
            }
            if (!Schema::hasColumn('subsidio_cupo_asignaciones', 'reversion_en')) {
                $table->timestamp('reversion_en')->nullable()->after('cancelacion_motivo');
                $table->unsignedBigInteger('reversion_por_user_id')->nullable()->after('reversion_en');
                $table->text('reversion_motivo')->nullable()->after('reversion_por_user_id');
            }

            $table->index(['asistencia_estado']);
            $table->index(['cancelada_en']);
        });
    }

    public function down(): void
    {
        Schema::table('subsidio_cupo_asignaciones', function (Blueprint $table) {
            foreach ([
                'asistencia_estado','cancelada_en','cancelada_por_user_id',
                'cancelacion_origen','cancelacion_motivo',
                'reversion_en','reversion_por_user_id','reversion_motivo',
            ] as $col) {
                if (Schema::hasColumn('subsidio_cupo_asignaciones', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};