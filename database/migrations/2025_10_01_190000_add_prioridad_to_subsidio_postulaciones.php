<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subsidio_postulaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('subsidio_postulaciones', 'prioridad_base')) {
                $table->smallInteger('prioridad_base')->nullable()->after('posicion');
            }
            if (!Schema::hasColumn('subsidio_postulaciones', 'prioridad_final')) {
                $table->smallInteger('prioridad_final')->nullable()->after('prioridad_base');
            }
            if (!Schema::hasColumn('subsidio_postulaciones', 'prioridad_calculada_en')) {
                $table->timestamp('prioridad_calculada_en')->nullable()->after('prioridad_final');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subsidio_postulaciones', function (Blueprint $table) {
            if (Schema::hasColumn('subsidio_postulaciones', 'prioridad_calculada_en')) {
                $table->dropColumn('prioridad_calculada_en');
            }
            if (Schema::hasColumn('subsidio_postulaciones', 'prioridad_final')) {
                $table->dropColumn('prioridad_final');
            }
            if (Schema::hasColumn('subsidio_postulaciones', 'prioridad_base')) {
                $table->dropColumn('prioridad_base');
            }
        });
    }
};