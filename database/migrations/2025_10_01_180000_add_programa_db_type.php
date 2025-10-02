<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Agregar 'programa_db' al ENUM de subsidio_preguntas.tipo
        DB::statement("
            ALTER TABLE subsidio_preguntas
            MODIFY COLUMN tipo ENUM(
                'texto','numero','boolean','seleccion_unica','seleccion_multiple','fecha',
                'email','telefono','parrafo','matrix_single','programa_db'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        // Si necesitas revertir, quita 'programa_db' del enum
        DB::statement("
            ALTER TABLE subsidio_preguntas
            MODIFY COLUMN tipo ENUM(
                'texto','numero','boolean','seleccion_unica','seleccion_multiple','fecha',
                'email','telefono','parrafo','matrix_single'
            ) NOT NULL
        ");
    }
};