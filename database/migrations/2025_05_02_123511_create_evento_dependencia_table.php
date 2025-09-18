<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evento_dependencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evento_id');
            $table->unsignedBigInteger('programadependencia_id');
            $table->timestamps();

            // Claves foráneas
            $table->foreign('evento_id')->references('id')->on('evento')->onDelete('cascade');
            $table->foreign('programadependencia_id')->references('id')->on('programadependencia')->onDelete('cascade');

            // Evitar duplicados
            $table->unique(['evento_id', 'programadependencia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_dependencia');
    }
};
