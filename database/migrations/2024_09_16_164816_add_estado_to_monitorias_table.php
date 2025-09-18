<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('monitorias', function (Blueprint $table) {
            $table->enum('estado', ['creado', 'autorizado', 'requiere_ajustes', 'aprobado'])->default('creado')->after('modalidad');
        });
    }

    public function down()
    {
        Schema::table('monitorias', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
