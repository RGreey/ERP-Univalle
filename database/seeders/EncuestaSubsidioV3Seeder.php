<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\EncuestaSubsidio;
use App\Models\PreguntaSubsidio;
use App\Models\OpcionSubsidio;
use App\Models\SubsidioMatrizFila;
use App\Models\SubsidioMatrizColumna;

class EncuestaSubsidioV3Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $encuesta = EncuestaSubsidio::create([
                'nombre'  => 'Encuesta Subsidio Alimenticio v3',
                'version' => 3,
                'estado'  => 'activa',
            ]);

            // Identificación
            $pNombre = PreguntaSubsidio::create(['titulo'=>'NOMBRE COMPLETO Y APELLIDOS','tipo'=>'texto','obligatoria'=>true,'grupo'=>'Identificación','orden_global'=>1]);
            $pDoc    = PreguntaSubsidio::create(['titulo'=>'NÚMERO DE DOCUMENTO DE IDENTIDAD','tipo'=>'texto','obligatoria'=>true,'grupo'=>'Identificación','orden_global'=>2]);
            $pCodigo = PreguntaSubsidio::create(['titulo'=>'NÚMERO DE CÓDIGO (COMPLETO)','tipo'=>'texto','obligatoria'=>true,'grupo'=>'Identificación','orden_global'=>3]);
            $pTel    = PreguntaSubsidio::create(['titulo'=>'NÚMERO TELÉFONO DE CONTACTO','tipo'=>'telefono','obligatoria'=>true,'grupo'=>'Identificación','orden_global'=>4]);
            $pEmail  = PreguntaSubsidio::create(['titulo'=>'CORREO ELECTRÓNICO','tipo'=>'email','obligatoria'=>true,'grupo'=>'Identificación','orden_global'=>5]);

            // Académicos
            $pProg = PreguntaSubsidio::create(['titulo'=>'PROGRAMA AL QUE PERTENECE','tipo'=>'seleccion_unica','obligatoria'=>true,'grupo'=>'Académicos','orden_global'=>6]);
            OpcionSubsidio::insert([
                ['pregunta_id'=>$pProg->id,'texto'=>'Administración de Empresas','peso'=>0,'orden'=>1],
                ['pregunta_id'=>$pProg->id,'texto'=>'Contaduría Pública','peso'=>0,'orden'=>2],
                ['pregunta_id'=>$pProg->id,'texto'=>'Tecnología en Producción Agroambiental','peso'=>0,'orden'=>3],
                ['pregunta_id'=>$pProg->id,'texto'=>'Tecnología en Desarrollo de Software','peso'=>0,'orden'=>4],
                ['pregunta_id'=>$pProg->id,'texto'=>'Ingeniería Industrial','peso'=>0,'orden'=>5],
            ]);

            // Matriz días vs sede (radio por fila)
            $pMatriz = PreguntaSubsidio::create([
                'titulo'=>'¿Qué días de la semana solicita el almuerzo y en qué sede?',
                'tipo'=>'matrix_single','obligatoria'=>true,'grupo'=>'Preferencias','orden_global'=>7
            ]);
            foreach (['Lunes','Martes','Miércoles','Jueves','Viernes'] as $i=>$dia) {
                SubsidioMatrizFila::create(['pregunta_id'=>$pMatriz->id,'etiqueta'=>$dia,'orden'=>$i+1]);
            }
            foreach ([['Caicedonia','caicedonia',1],['Sevilla','sevilla',2],['No necesito este día','no_dia',3]] as [$et,$val,$ord]) {
                SubsidioMatrizColumna::create(['pregunta_id'=>$pMatriz->id,'etiqueta'=>$et,'valor'=>$val,'orden'=>$ord]);
            }

            // Prioridad base (residencia/estrato/jornada)
            $pResid = PreguntaSubsidio::create(['titulo'=>'¿Dónde resides?','tipo'=>'seleccion_unica','obligatoria'=>true,'grupo'=>'Prioridad (base)','orden_global'=>8]);
            OpcionSubsidio::insert([
                ['pregunta_id'=>$pResid->id,'texto'=>'Rural / Vereda','peso'=>0,'orden'=>1],
                ['pregunta_id'=>$pResid->id,'texto'=>'Foráneo','peso'=>0,'orden'=>2],
                ['pregunta_id'=>$pResid->id,'texto'=>'Urbano','peso'=>0,'orden'=>3],
            ]);

            $pEstr = PreguntaSubsidio::create(['titulo'=>'Estrato (si eres urbano)','tipo'=>'seleccion_unica','obligatoria'=>true,'grupo'=>'Prioridad (base)','orden_global'=>9]);
            OpcionSubsidio::insert([
                ['pregunta_id'=>$pEstr->id,'texto'=>'No aplica','peso'=>0,'orden'=>0],
                ['pregunta_id'=>$pEstr->id,'texto'=>'Estrato 1','peso'=>0,'orden'=>1],
                ['pregunta_id'=>$pEstr->id,'texto'=>'Estrato 2','peso'=>0,'orden'=>2],
                ['pregunta_id'=>$pEstr->id,'texto'=>'Estrato 3 o más','peso'=>0,'orden'=>3],
            ]);

            $pJor = PreguntaSubsidio::create(['titulo'=>'Jornada','tipo'=>'seleccion_unica','obligatoria'=>true,'grupo'=>'Prioridad (base)','orden_global'=>10]);
            OpcionSubsidio::insert([
                ['pregunta_id'=>$pJor->id,'texto'=>'Doble','peso'=>0,'orden'=>1],
                ['pregunta_id'=>$pJor->id,'texto'=>'Simple','peso'=>0,'orden'=>2],
                ['pregunta_id'=>$pJor->id,'texto'=>'Nocturna','peso'=>0,'orden'=>3],
            ]);

            // Prioridad automática (F1/F2)
            $pF1 = PreguntaSubsidio::create([
                'titulo'=>'¿Cuentas con alguna condición de protección social? (grupo étnico, víctima del conflicto, discapacidad)',
                'tipo'=>'seleccion_unica','obligatoria'=>true,'grupo'=>'Prioridad (ajustes)','orden_global'=>11
            ]);
            OpcionSubsidio::insert([
                ['pregunta_id'=>$pF1->id,'texto'=>'Sí','peso'=>1,'orden'=>1],
                ['pregunta_id'=>$pF1->id,'texto'=>'No','peso'=>0,'orden'=>2],
            ]);

            $pF2 = PreguntaSubsidio::create([
                'titulo'=>'¿Eres madre cabeza de hogar?',
                'tipo'=>'seleccion_unica','obligatoria'=>true,'grupo'=>'Prioridad (ajustes)','orden_global'=>12
            ]);
            OpcionSubsidio::insert([
                ['pregunta_id'=>$pF2->id,'texto'=>'Sí','peso'=>1,'orden'=>1],
                ['pregunta_id'=>$pF2->id,'texto'=>'No','peso'=>0,'orden'=>2],
            ]);

            // Párrafo informativo
            $pInfo = PreguntaSubsidio::create([
                'titulo'=>'Instrucciones importantes',
                'descripcion'=>"Tras la revisión, Bienestar te contactará por correo si hay correcciones.\n"
                    ."La asignación se realiza con base en los cupos de cada sede.\n"
                    ."Adjunta un único PDF con todos los soportes requeridos.",
                'tipo'=>'parrafo','obligatoria'=>false,'grupo'=>'Información','orden_global'=>13
            ]);

            // Pivot
            DB::table('subsidio_encuesta_pregunta')->insert([
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pNombre->id, 'orden'=>1,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pDoc->id,    'orden'=>2,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pCodigo->id, 'orden'=>3,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pTel->id,    'orden'=>4,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pEmail->id,  'orden'=>5,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pProg->id,   'orden'=>6,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pMatriz->id, 'orden'=>7,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pResid->id,  'orden'=>8,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pEstr->id,   'orden'=>9,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pJor->id,    'orden'=>10,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pF1->id,     'orden'=>11,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pF2->id,     'orden'=>12,'obligatoria'=>true],
                ['encuesta_id'=>$encuesta->id,'pregunta_id'=>$pInfo->id,   'orden'=>13,'obligatoria'=>false],
            ]);
        });
    }
}