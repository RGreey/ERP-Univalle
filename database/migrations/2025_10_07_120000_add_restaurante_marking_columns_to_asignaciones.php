<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subsidio_cupo_asignaciones', function (Blueprint $table) {
            // Columna de timestamp (si no existe)
            if (!Schema::hasColumn('subsidio_cupo_asignaciones', 'asistencia_marcada_en')) {
                $table->timestamp('asistencia_marcada_en')->nullable()->after('asistencia_estado');
            }

            // Columna FK (si no existe)
            if (!Schema::hasColumn('subsidio_cupo_asignaciones', 'asistencia_marcada_por_user_id')) {
                $table->unsignedBigInteger('asistencia_marcada_por_user_id')->nullable()->after('asistencia_marcada_en');
            }

            // Foreign key con nombre corto (evita el límite de 64 chars de MySQL)
            // Nota: si ya existiera, MySQL lanzaría error; en tu caso no existe porque falló antes.
            $table->foreign('asistencia_marcada_por_user_id', 'asig_marcada_user_fk')
                  ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subsidio_cupo_asignaciones', function (Blueprint $table) {
            // Elimina FK por nombre corto y luego las columnas (si existen)
            if (Schema::hasColumn('subsidio_cupo_asignaciones', 'asistencia_marcada_por_user_id')) {
                // Si el constraint no existiera, puedes comentar esta línea temporalmente para hacer el down.
                $table->dropForeign('asig_marcada_user_fk');
                $table->dropColumn('asistencia_marcada_por_user_id');
            }

            if (Schema::hasColumn('subsidio_cupo_asignaciones', 'asistencia_marcada_en')) {
                $table->dropColumn('asistencia_marcada_en');
            }
        });
    }
};