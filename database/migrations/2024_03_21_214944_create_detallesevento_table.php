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
        Schema::create('detallesevento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento')->constrained('evento');
            $table->boolean('transporte');
            $table->boolean('audio');
            $table->boolean('proyeccion');
            $table->boolean('internet');
            $table->text('otros')->nullable();
            $table->boolean('diseñoPublicitario');
            $table->boolean('redes');
            $table->boolean('correo');
            $table->boolean('whatsapp');
            $table->boolean('personal_recibo');
            $table->boolean('seguridad');
            $table->boolean('bienvenida');
            $table->boolean('defensoria_civil');
            $table->string('participantes')->nullable();
            $table->boolean('cubrimiento_medios');
            $table->boolean('servicio_general');
            $table->boolean('otro_Recurso');
            $table->timestamps();

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detallesevento');
    }
};
