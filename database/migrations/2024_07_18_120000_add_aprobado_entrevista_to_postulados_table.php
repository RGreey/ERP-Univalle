<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cambiar el enum del campo 'estado' para agregar 'aprobado_entrevista'
        DB::statement("ALTER TABLE postulados MODIFY estado ENUM('pendiente', 'aprobado_entrevista', 'aprobado', 'rechazado') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver al enum anterior
        DB::statement("ALTER TABLE postulados MODIFY estado ENUM('pendiente', 'aprobado', 'rechazado') NOT NULL");
    }
};
