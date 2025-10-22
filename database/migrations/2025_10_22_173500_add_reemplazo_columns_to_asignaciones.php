<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subsidio_cupo_asignaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('subsidio_cupo_asignaciones', 'es_reemplazo')) {
                $table->boolean('es_reemplazo')->default(false)->after('reversion_motivo');
            }
            if (!Schema::hasColumn('subsidio_cupo_asignaciones', 'reemplaza_asignacion_id')) {
                $table->unsignedBigInteger('reemplaza_asignacion_id')->nullable()->after('es_reemplazo');
                // FK opcional con nombre corto para evitar límite de 64 chars
                $table->foreign('reemplaza_asignacion_id', 'asig_reemplaza_fk')
                      ->references('id')->on('subsidio_cupo_asignaciones')->nullOnDelete();
            }
        });

        // Hacer nullable postulacion_id (sin requerir doctrine/dbal)
        try {
            DB::statement('ALTER TABLE subsidio_cupo_asignaciones MODIFY postulacion_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // Ignorar si ya es NULL o el motor no lo permite (en la mayoría de MySQL funciona)
        }
    }

    public function down(): void
    {
        Schema::table('subsidio_cupo_asignaciones', function (Blueprint $table) {
            if (Schema::hasColumn('subsidio_cupo_asignaciones', 'reemplaza_asignacion_id')) {
                try { $table->dropForeign('asig_reemplaza_fk'); } catch (\Throwable $e) {}
                $table->dropColumn('reemplaza_asignacion_id');
            }
            if (Schema::hasColumn('subsidio_cupo_asignaciones', 'es_reemplazo')) {
                $table->dropColumn('es_reemplazo');
            }
        });

        // Nota: no revertimos postulacion_id a NOT NULL para evitar romper datos creados por standby
    }
};