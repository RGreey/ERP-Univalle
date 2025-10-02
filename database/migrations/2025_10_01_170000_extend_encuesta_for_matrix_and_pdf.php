<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Extender ENUM de subsidio_preguntas.tipo
        // Agregamos: 'email','telefono','parrafo','matrix_single'
        DB::statement("
            ALTER TABLE subsidio_preguntas
            MODIFY COLUMN tipo ENUM(
                'texto','numero','boolean','seleccion_unica','seleccion_multiple','fecha',
                'email','telefono','parrafo','matrix_single'
            ) NOT NULL
        ");

        // 2) Tablas de definición para preguntas tipo matriz
        Schema::create('subsidio_matriz_filas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained('subsidio_preguntas')->cascadeOnDelete();
            $table->string('etiqueta');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->index(['pregunta_id','orden']);
        });

        Schema::create('subsidio_matriz_columnas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained('subsidio_preguntas')->cascadeOnDelete();
            $table->string('etiqueta');
            $table->string('valor'); // slug/valor que guardaremos en respuestas (ej: 'caicedonia', 'sevilla', 'no_dia')
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
            $table->unique(['pregunta_id','valor']);
            $table->index(['pregunta_id','orden']);
        });

        // 3) Respuestas: JSON para matriz
        Schema::table('subsidio_respuestas', function (Blueprint $table) {
            if (!Schema::hasColumn('subsidio_respuestas', 'respuesta_json')) {
                $table->json('respuesta_json')->nullable()->after('opcion_ids');
            }
        });

        // 4) Postulación: un solo PDF unificado
        Schema::table('subsidio_postulaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('subsidio_postulaciones', 'documento_pdf')) {
                $table->string('documento_pdf')->nullable()->after('posicion');
            }
        });

        // 5) Si tu tabla convocatorias_subsidio aún no tiene encuesta_id/recepcion_habilitada, añádelos aquí
        if (Schema::hasTable('convocatorias_subsidio')) {
            Schema::table('convocatorias_subsidio', function (Blueprint $table) {
                if (!Schema::hasColumn('convocatorias_subsidio', 'encuesta_id')) {
                    $table->foreignId('encuesta_id')->nullable()->after('estado')->constrained('subsidio_encuestas')->nullOnDelete();
                }
                if (!Schema::hasColumn('convocatorias_subsidio', 'recepcion_habilitada')) {
                    $table->boolean('recepcion_habilitada')->default(true)->after('encuesta_id');
                }
            });
        }
    }

    public function down(): void
    {
        // Nota: revertir ENUM es delicado. Si necesitas revertir, ajusta la lista manualmente.
        Schema::dropIfExists('subsidio_matriz_columnas');
        Schema::dropIfExists('subsidio_matriz_filas');

        if (Schema::hasColumn('subsidio_respuestas', 'respuesta_json')) {
            Schema::table('subsidio_respuestas', function (Blueprint $table) {
                $table->dropColumn('respuesta_json');
            });
        }
        if (Schema::hasColumn('subsidio_postulaciones', 'documento_pdf')) {
            Schema::table('subsidio_postulaciones', function (Blueprint $table) {
                $table->dropColumn('documento_pdf');
            });
        }
    }
};