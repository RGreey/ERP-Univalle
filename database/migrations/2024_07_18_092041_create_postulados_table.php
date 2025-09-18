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
        Schema::create('postulados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user')->constrained('users')->onDelete('cascade');
            $table->foreignId('convocatoria')->constrained('convocatorias')->onDelete('cascade');
            $table->foreignId('monitoria')->constrained('monitorias')->onDelete('cascade');
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postulados');
    }
};
