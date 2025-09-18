<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paquetes_evidencias', function (Blueprint $table) {
            $table->id();
            $table->enum('sede', ['MI','VC','LI','Nodo']);
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->text('descripcion_general')->nullable();
            $table->string('archivo_pdf')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();

            $table->index(['sede','mes','anio']);
            $table->foreign('usuario_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('fotos_evidencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paquete_id');
            $table->unsignedBigInteger('actividad_id');
            $table->string('archivo');
            $table->string('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->foreign('paquete_id')->references('id')->on('paquetes_evidencias')->cascadeOnDelete();
            $table->foreign('actividad_id')->references('id')->on('actividades_mantenimiento')->cascadeOnDelete();
            $table->index(['paquete_id','actividad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos_evidencia');
        Schema::dropIfExists('paquetes_evidencias');
    }
};


