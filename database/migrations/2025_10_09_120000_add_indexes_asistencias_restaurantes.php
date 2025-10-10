<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subsidio_cupo_asignaciones', function (Blueprint $table) {
            if (! $this->indexExists('subsidio_cupo_asignaciones', 'cupo_asig_asistencia_estado_idx')) {
                $table->index('asistencia_estado', 'cupo_asig_asistencia_estado_idx');
            }
        });

        Schema::table('subsidio_cupos_diarios', function (Blueprint $table) {
            if (! $this->indexExists('subsidio_cupos_diarios', 'cupos_convocatoria_fecha_sede_idx')) {
                $table->index(['convocatoria_id','fecha','sede'], 'cupos_convocatoria_fecha_sede_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subsidio_cupo_asignaciones', function (Blueprint $table) {
            $this->dropIndexIfExists('subsidio_cupo_asignaciones', 'cupo_asig_asistencia_estado_idx');
        });
        Schema::table('subsidio_cupos_diarios', function (Blueprint $table) {
            $this->dropIndexIfExists('subsidio_cupos_diarios', 'cupos_convocatoria_fecha_sede_idx');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            return collect(DB::select("SHOW INDEX FROM {$table}"))->pluck('Key_name')->contains($index);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        try {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
        } catch (\Throwable $e) {}
    }
};