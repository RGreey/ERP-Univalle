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
    public function up()
    {
        // Agregar 'rechazado' al enum de estado
        DB::statement("ALTER TABLE monitorias MODIFY COLUMN estado ENUM('creado', 'autorizado', 'requiere_ajustes', 'aprobado', 'rechazado')");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Remover 'rechazado' del enum de estado
        DB::statement("ALTER TABLE monitorias MODIFY COLUMN estado ENUM('creado', 'autorizado', 'requiere_ajustes', 'aprobado')");
    }
};
