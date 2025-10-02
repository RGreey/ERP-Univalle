<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EncuestaSubsidio;
use App\Models\PreguntaSubsidio;
use App\Models\OpcionSubsidio;
use Illuminate\Support\Facades\DB;

class SubsidioEncuestaBaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $encuesta = EncuestaSubsidio::create([
                'nombre' => 'Encuesta Subsidio Alimenticio v1',
                'version' => 1,
                'estado'  => 'activa',
            ]);

            // Pregunta: Residencia
            $pResidencia = PreguntaSubsidio::create([
                'titulo' => '¿Dónde resides?',
                'descripcion' => 'Selecciona tu tipo de residencia.',
                'tipo' => 'seleccion_unica',
                'obligatoria' => true,
                'grupo' => 'Datos socioeconómicos',
                'orden_global' => 1,
            ]);
            OpcionSubsidio::insert([
                ['pregunta_id' => $pResidencia->id, 'texto' => 'Rural/Vereda', 'peso' => 0, 'orden' => 1],
                ['pregunta_id' => $pResidencia->id, 'texto' => 'Foráneo', 'peso' => 0, 'orden' => 2],
                ['pregunta_id' => $pResidencia->id, 'texto' => 'Urbano', 'peso' => 0, 'orden' => 3],
            ]);

            // Pregunta: Estrato (solo aplica si Urbano, pero la dejamos general por ahora)
            $pEstrato = PreguntaSubsidio::create([
                'titulo' => '¿Cuál es tu estrato (si eres urbano)?',
                'descripcion' => 'Si no eres urbano, selecciona "No aplica".',
                'tipo' => 'seleccion_unica',
                'obligatoria' => true,
                'grupo' => 'Datos socioeconómicos',
                'orden_global' => 2,
            ]);
            OpcionSubsidio::insert([
                ['pregunta_id' => $pEstrato->id, 'texto' => 'No aplica', 'peso' => 0, 'orden' => 0],
                ['pregunta_id' => $pEstrato->id, 'texto' => 'Estrato 1', 'peso' => 0, 'orden' => 1],
                ['pregunta_id' => $pEstrato->id, 'texto' => 'Estrato 2', 'peso' => 0, 'orden' => 2],
                ['pregunta_id' => $pEstrato->id, 'texto' => 'Estrato 3 o más', 'peso' => 0, 'orden' => 3],
            ]);

            // Pregunta: Jornada
            $pJornada = PreguntaSubsidio::create([
                'titulo' => '¿Cuál es tu jornada?',
                'descripcion' => 'Selecciona tu jornada actual.',
                'tipo' => 'seleccion_unica',
                'obligatoria' => true,
                'grupo' => 'Académicos',
                'orden_global' => 3,
            ]);
            OpcionSubsidio::insert([
                ['pregunta_id' => $pJornada->id, 'texto' => 'Doble', 'peso' => 0, 'orden' => 1],
                ['pregunta_id' => $pJornada->id, 'texto' => 'Simple', 'peso' => 0, 'orden' => 2],
                ['pregunta_id' => $pJornada->id, 'texto' => 'Nocturna', 'peso' => 0, 'orden' => 3],
            ]);

            // Pregunta: F1 (protección social)
            $pF1 = PreguntaSubsidio::create([
                'titulo' => '¿Cuentas con alguna condición de protección social?',
                'descripcion' => 'Grupo étnico, víctima del conflicto o discapacidad (requiere soporte).',
                'tipo' => 'seleccion_unica',
                'obligatoria' => true,
                'grupo' => 'Prioridad',
                'orden_global' => 4,
            ]);
            OpcionSubsidio::insert([
                ['pregunta_id' => $pF1->id, 'texto' => 'Sí', 'peso' => 1, 'orden' => 1],
                ['pregunta_id' => $pF1->id, 'texto' => 'No', 'peso' => 0, 'orden' => 2],
            ]);

            // Pregunta: F2 (madre cabeza de hogar)
            $pF2 = PreguntaSubsidio::create([
                'titulo' => '¿Eres madre cabeza de hogar?',
                'descripcion' => 'Aplica solo si aportas el soporte requerido.',
                'tipo' => 'seleccion_unica',
                'obligatoria' => true,
                'grupo' => 'Prioridad',
                'orden_global' => 5,
            ]);
            OpcionSubsidio::insert([
                ['pregunta_id' => $pF2->id, 'texto' => 'Sí', 'peso' => 1, 'orden' => 1],
                ['pregunta_id' => $pF2->id, 'texto' => 'No', 'peso' => 0, 'orden' => 2],
            ]);

            // Pivot simple: usar orden_global como orden local
            DB::table('subsidio_encuesta_pregunta')->insert([
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pResidencia->id, 'orden' => 1, 'obligatoria' => true],
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pEstrato->id,    'orden' => 2, 'obligatoria' => true],
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pJornada->id,    'orden' => 3, 'obligatoria' => true],
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pF1->id,         'orden' => 4, 'obligatoria' => true],
                ['encuesta_id' => $encuesta->id, 'pregunta_id' => $pF2->id,         'orden' => 5, 'obligatoria' => true],
            ]);
        });
    }
}