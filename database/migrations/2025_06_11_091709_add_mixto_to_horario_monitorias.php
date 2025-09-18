<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el enum de horario para incluir 'mixto'
        DB::statement("ALTER TABLE monitorias MODIFY COLUMN horario ENUM('diurno', 'nocturno', 'mixto')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el enum de horario a sus valores originales
        DB::statement("ALTER TABLE monitorias MODIFY COLUMN horario ENUM('diurno', 'nocturno')");
    }
};
