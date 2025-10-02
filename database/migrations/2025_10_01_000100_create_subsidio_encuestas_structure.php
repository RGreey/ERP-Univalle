<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subsidio_encuestas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedInteger('version')->default(1);
            $table->enum('estado', ['activa', 'inactiva'])->default('activa');
            $table->timestamps();
        });

        Schema::create('subsidio_preguntas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['texto','numero','boolean','seleccion_unica','seleccion_multiple','fecha']);
            $table->boolean('obligatoria')->default(true);
            $table->string('grupo')->nullable();
            $table->unsignedInteger('orden_global')->default(0);
            $table->timestamps();
        });

        Schema::create('subsidio_opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained('subsidio_preguntas')->cascadeOnDelete();
            $table->string('texto');
            $table->decimal('peso', 8, 2)->default(0);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('subsidio_encuesta_pregunta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encuesta_id')->constrained('subsidio_encuestas')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('subsidio_preguntas')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('obligatoria')->default(true);
            $table->decimal('peso_override', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['encuesta_id','pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsidio_encuesta_pregunta');
        Schema::dropIfExists('subsidio_opciones');
        Schema::dropIfExists('subsidio_preguntas');
        Schema::dropIfExists('subsidio_encuestas');
    }
};