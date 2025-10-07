<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subsidio_reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Metadatos del reporte
            $table->string('tipo', 50);                 // ej: 'servicio', 'higiene', 'trato', 'sugerencia'
            $table->string('titulo', 140)->nullable();  // opcional
            $table->text('descripcion');

            // Contexto
            $table->string('sede', 30)->nullable();     // 'caicedonia' | 'sevilla' | null
            $table->string('origen', 20)->default('app'); // app | web | otro

            // Flujo
            $table->string('estado', 20)->default('pendiente'); // pendiente | en_proceso | resuelto | archivado
            $table->text('admin_respuesta')->nullable();
            $table->timestamp('respondido_en')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['estado', 'created_at'], 'rep_estado_fecha_idx');
            $table->index('user_id');
            $table->index('sede');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsidio_reportes');
    }
};