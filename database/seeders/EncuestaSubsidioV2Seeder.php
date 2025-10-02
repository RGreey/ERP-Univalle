<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\EncuestaSubsidio;
use App\Models\PreguntaSubsidio;
use App\Models\OpcionSubsidio;
use App\Models\SubsidioMatrizFila;
use App\Models\SubsidioMatrizColumna;

class EncuestaSubsidioV2Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $encuesta = EncuestaSubsidio::create([
                'nombre' => 'Encuesta Subsidio Alimenticio v2',
                'version' => 2,
                'estado'  => 'activa',
            ]);

            // Campos de identificación (pueden prellenarse en UI)
            $pNombre = PreguntaSubsidio::create([
                'titulo' => 'NOMBRE COMPLETO Y APELLIDOS',
                'tipo' => 'texto', 'obligatoria' => true, 'grupo' => 'Identificación', 'orden_global' => 1,
            ]);
            $pDocumento = PreguntaSubsidio::create([
                'titulo' => 'NÚMERO DE DOCUMENTO DE IDENTIDAD',
                'tipo' => 'texto', 'obligatoria' => true, 'grupo' => 'Identificación', 'orden_global' => 2,
            ]);
            $pCodigo = PreguntaSubsidio::create([
                'titulo' => 'NÚMERO DE CÓDIGO (COMPLETO)',
                'tipo' => 'texto', 'obligatoria' => true, 'grupo' => 'Identificación', 'orden_global' => 3,
            ]);
            $pTelefono = PreguntaSubsidio::create([
                'titulo' => 'NÚMERO TELÉFONO DE CONTACTO',
                'tipo' => 'telefono', 'obligatoria' => true, 'grupo' => 'Identificación', 'orden_global' => 4,
            ]);
            $pCorreo = PreguntaSubsidio::create([
                'titulo' => 'CORREO ELECTRÓNICO',
                'tipo' => 'email', 'obligatoria' => true, 'grupo' => 'Identificación', 'orden_global' => 5,
            ]);

            // Programa
            $pPrograma = PreguntaSubsidio::create([
                'titulo' => 'PROGRAMA AL QUE PERTENECE',
                'tipo' => 'seleccion_unica', 'obligatoria' => true, 'grupo' => 'Académicos', 'orden_global' => 6,
            ]);
            OpcionSubsidio::insert([
                ['pregunta_id' => $pPrograma->id, 'texto' => 'Administración de Empresas', 'peso' => 0, 'orden' => 1],
                ['pregunta_id' => $pPrograma->id, 'texto' => 'Contaduría Pública', 'peso' => 0, 'orden' => 2],
                ['pregunta_id' => $pPrograma->id, 'texto' => 'Tecnología en Producción Agroambiental', 'peso' => 0, 'orden' => 3],
                ['pregunta_id' => $pPrograma->id, 'texto' => 'Tecnología en Desarrollo de Software', 'peso' => 0, 'orden' => 4],
                ['pregunta_id' => $pPrograma->id, 'texto' => 'Ingeniería Industrial', 'peso' => 0, 'orden' => 5],
            ]);

            // Matriz días vs sede
            $pMatriz = PreguntaSubsidio::create([
                'titulo' => '¿Qué días de la semana solicita el almuerzo y en qué sede?',
                'tipo' => 'matrix_single', 'obligatoria' => true, 'grupo' => 'Preferencias', 'orden_global' => 7,
            ]);

            foreach (['Lunes','Martes','Miércoles','Jueves','Viernes'] as $i => $dia) {
                SubsidioMatrizFila::create([
                    'pregunta_id' => $pMatriz->id, 'etiqueta' => $dia, 'orden' => $i+1
                ]);
            }
            $cols = [
                ['etiqueta' => 'Caicedonia', 'valor' => 'caicedonia', 'orden' => 1],
                ['etiqueta' => 'Sevilla',    'valor' => 'sevilla',    'orden' => 2],
                ['etiqueta' => 'No necesito este día', 'valor' => 'no_dia', 'orden' => 3],
            ];
            foreach ($cols as $c) {
                SubsidioMatrizColumna::create(array_merge($c, ['pregunta_id' => $pMatriz->id]));
            }

            // Párrafo con instrucciones
            $pInfo = PreguntaSubsidio::create([
                'titulo' => 'Instrucciones importantes',
                'descripcion' => "Tras la revisión de las solicitudes, el equipo de Bienestar se comunicará por correo para correcciones.\n"
                    ."La asignación de almuerzos se realiza con base en los cupos de cada sede.\n"
                    ."Asegúrate de adjuntar un único PDF con todos los soportes requeridos.",
                'tipo' => 'parrafo', 'obligatoria' => false, 'grupo' => 'Información', 'orden_global' => 8,
            ]);

            // Pivot: usar orden_global como orden local
            DB::table('subsidio_encuesta_pregunta')->insert([
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pNombre->id,    'orden' => 1, 'obligatoria' => true],
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pDocumento->id, 'orden' => 2, 'obligatoria' => true],
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pCodigo->id,    'orden' => 3, 'obligatoria' => true],
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pTelefono->id,  'orden' => 4, 'obligatoria' => true],
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pCorreo->id,    'orden' => 5, 'obligatoria' => true],
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pPrograma->id,  'orden' => 6, 'obligatoria' => true],
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pMatriz->id,    'orden' => 7, 'obligatoria' => true],
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pInfo->id,      'orden' => 8, 'obligatoria' => false],
            ]);
        });
    }
}