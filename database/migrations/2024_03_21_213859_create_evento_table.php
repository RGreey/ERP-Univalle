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
        Schema::create('evento', function (Blueprint $table) {
            $table->id();
            $table->string('nombreEvento');
            $table->string('propositoEvento');
            $table->foreignId('programadependencia')->constrained('programadependencia');
            $table->unsignedBigInteger('user');
            $table->foreign('user')->references('id')->on('users');
            $table->foreignId('lugar')->constrained('lugar');
            $table->foreignId('espacio')->constrained('espacio');
            $table->date('fechaRealizacion');
            $table->time('horaInicio');
            $table->time('horaFin');
            $table->enum('estado', ['Creado', 'Aceptado', 'Rechazado', 'Cancelado', 'Cerrado'])->default('Creado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento');
    }
};
