<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('convocatorias_subsidio')) {
            return;
        }

        Schema::table('convocatorias_subsidio', function (Blueprint $table) {
            // fecha_inicio_beneficio
            if (!Schema::hasColumn('convocatorias_subsidio', 'fecha_inicio_beneficio')) {
                if (Schema::hasColumn('convocatorias_subsidio', 'fecha_cierre')) {
                    $table->date('fecha_inicio_beneficio')->nullable()->after('fecha_cierre');
                } else {
                    $table->date('fecha_inicio_beneficio')->nullable();
                }
            }

            // fecha_fin_beneficio
            if (!Schema::hasColumn('convocatorias_subsidio', 'fecha_fin_beneficio')) {
                if (Schema::hasColumn('convocatorias_subsidio', 'fecha_inicio_beneficio')) {
                    $table->date('fecha_fin_beneficio')->nullable()->after('fecha_inicio_beneficio');
                } else {
                    $table->date('fecha_fin_beneficio')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('convocatorias_subsidio')) {
            return;
        }

        Schema::table('convocatorias_subsidio', function (Blueprint $table) {
            if (Schema::hasColumn('convocatorias_subsidio', 'fecha_fin_beneficio')) {
                $table->dropColumn('fecha_fin_beneficio');
            }
            if (Schema::hasColumn('convocatorias_subsidio', 'fecha_inicio_beneficio')) {
                $table->dropColumn('fecha_inicio_beneficio');
            }
        });
    }
};