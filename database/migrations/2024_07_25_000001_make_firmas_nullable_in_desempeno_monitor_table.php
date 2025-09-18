<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('desempeno_monitor', function (Blueprint $table) {
            $table->longText('firma_evaluador')->nullable()->change();
            $table->longText('firma_evaluado')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('desempeno_monitor', function (Blueprint $table) {
            $table->longText('firma_evaluador')->nullable(false)->change();
            $table->longText('firma_evaluado')->nullable(false)->change();
        });
    }
}; 