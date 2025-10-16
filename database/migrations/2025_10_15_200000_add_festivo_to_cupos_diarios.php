<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subsidio_cupos_diarios', function (Blueprint $table) {
            if (!Schema::hasColumn('subsidio_cupos_diarios','es_festivo')) {
                $table->boolean('es_festivo')->default(false)->after('fecha');
            }
            if (!Schema::hasColumn('subsidio_cupos_diarios','festivo_motivo')) {
                $table->string('festivo_motivo', 180)->nullable()->after('es_festivo');
            }
            $table->index(['fecha','es_festivo']);
        });
    }

    public function down(): void
    {
        Schema::table('subsidio_cupos_diarios', function (Blueprint $table) {
            if (Schema::hasColumn('subsidio_cupos_diarios','festivo_motivo')) {
                $table->dropColumn('festivo_motivo');
            }
            if (Schema::hasColumn('subsidio_cupos_diarios','es_festivo')) {
                $table->dropColumn('es_festivo');
            }
        });
    }
};