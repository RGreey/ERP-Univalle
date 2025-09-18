<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateEstadoMonitoriasTable extends Migration
{
    public function up()
    {
        Schema::table('monitorias', function (Blueprint $table) {
            // Primero eliminamos la columna estado existente
            $table->dropColumn('estado');
        });

        Schema::table('monitorias', function (Blueprint $table) {
            // Luego creamos la nueva columna estado con los nuevos valores
            $table->enum('estado', ['creado', 'autorizado', 'requiere_ajustes', 'aprobado'])->default('creado')->after('modalidad');
        });

        // Modificar el enum de horario para incluir 'mixto'
        DB::statement("ALTER TABLE monitorias MODIFY COLUMN horario ENUM('diurno', 'nocturno', 'mixto')");
    }

    public function down()
    {
        Schema::table('monitorias', function (Blueprint $table) {
            // En caso de rollback, volvemos a los estados anteriores
            $table->dropColumn('estado');
            $table->enum('estado', ['aprobado_solicitante', 'pendiente_revision_secretaria', 'autorizado_secretaria', 'rechazado_secretaria', 'aprobado'])->default('aprobado_solicitante')->after('modalidad');
        });

        // Revertir el enum de horario
        DB::statement("ALTER TABLE monitorias MODIFY COLUMN horario ENUM('diurno', 'nocturno')");
    }
} 