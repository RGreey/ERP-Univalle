<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActividadMantenimiento;

class CargarActividadesDefinidas extends Command
{
    protected $signature = 'mantenimiento:cargar-actividades-definidas';
    protected $description = 'Carga las 23 actividades de mantenimiento definidas sin fechas ni semanas';

    public function handle(): int
    {
        $this->info('Cargando actividades definidas...');

        $mapFrecuencia = function (string $txt): string {
            $t = strtolower(trim($txt));
            return match ($t) {
                'anual' => 'anual',
                'trimestral' => 'trimestral',
                'cuatrimestral' => 'cuatrimestral',
                'mensual' => 'mensual',
                default => 'cuando_se_requiera',
            };
        };

        $acts = [
            [1,'Revisar señales de riesgo electrico','Revisar que todas las señales de riesgo electrico se encuentren en buen estado, de no ser asi cambiarlos','Anual',null,'Auxiliar de Servicios Generales','N/A'],
            [2,'Visita técnica e inspección','Visita tecnica e inspección de seguridad año 2025 a la Sede Regional Caicedonia','Anual','Coordinación Administrativa','Benemerito cuerpo de Bombero','Coordinación Administrativa'],
            [3,'Mantenimiento Aires Acondicionados','Mantenimientos Aires Acondicionador Sede Valle del Cauca y Maria Inmaculada','Anual','Coordinación Administrativa',null,'Coordinación Administrativa'],
            [4,'Fumigación','Fumigar todas los espacios de la universidad','Anual','Coordinación Administrativa','SERVISAM','Coordinación Administrativa'],
            [5,'Mantenimiento de Red Eléctrica','Mantenimiento y revisión de Red Eléctrica','Anual','Coordinación Administrativa','JESUS HERNANDO LUNA ORTIZ','Coordinación Administrativa'],
            [6,'Limpieza de Canales','Limpiar y verificar el estado de las canales en todos los espacios de la universidad.','Cuatrimestral','Auxiliar de Servicios Generales-DIU','N/A','Auxiliar de Servicios Generales-DIU'],
            [7,'Revisión de Extintores','Revisión y mantenimiento de extintores','Trimestral','Auxiliar de Servicios Varios/Profesional Salud Ocupacional','Benemerito cuerpo de Bombero','Auxiliar de Servicios Varios/Profesional Salud Ocupacional'],
            [8,'Revisión de zonas verdes','Manejo de arvenses mensualmente (Deshierbar-guadañar) y arreglo de jardines, poda de arboles','Mensual','Auxiliar de Servicios Varios','N/A','Auxiliar de Servicios Varios'],
            [9,'Recorrido por las instalaciones','Recorrido por las instalciones verificando goteras, chapas, enchufes y sillas','Mensual','Auxiliar de Servicios Generales','N/A','Auxiliar de Servicios Generales'],
            [10,'Mantenimiento de pintura','.Mantenimiento de pintura al área de psicología, entrada de laboratorio y baño principal ...','Cuando se Requiera','Auxiliar de Servicios Generales/DIU','N/A','Auxiliar de Servicios Generales/DIU'],
            [11,'Mantenimiento salas de sistemas','.Mantenimienti de pintura sala D','Cuando se Requiera','Auxiliar de Servicios Generales','N/A','Auxiliar de Servicios Generales'],
            [12,'Mantenimiento de fachadas','Labores de barrido y recogida de los residuos tanto en la zona externa como interna del Campus ...','Cuando se Requiera','Auxiliar de Servicios Generales-DIU','N/A','Auxiliar de Servicios Generales-DIU'],
            [13,'Mantenimiento de Baños','. Mantenimiento de pintura soportes de lavamanos Baños MI. Adecuación baño laboratorios. Cambio de perillo y arbol a dos baños de la MI','Cuando se Requiera','Auxiliar de Servicios Generales/DIU','N/A','Auxiliar de Servicios Generales/DIU'],
            [14,'Mantenimiento de iluminación','Cambio de canaletas en los salones 4 y 5 VC. Cambio de cableado y de lamparas ...','Cuando se Requiera','Auxiliar de Servicios Generales','N/A','Auxiliar de Servicios Generales'],
            [15,'Mantenimiento zonas de espacimiento','Mantenimiento de pintura a los patios ubicados en el segundo pido del edificio María Inmaculada, ...','Cuando se Requiera','Auxiliar de Servicios Generales','N/A','Auxiliar de Servicios Generales'],
            [16,'Mantenimiento portería, cocina','Mantenimiento portería, cocina','Cuando se Requiera','DIU','N/A','DIU'],
            [17,'Mantenimiento a las señalizaciones de seguridad','Demarcación de las escaleras con cinta antideslizante, demarcación de líneas de seguridad...','Cuando se Requiera','Auxiliar de Servicios Generales','N/A','Auxiliar de Servicios Generales'],
            [18,'Mantenimiento de oficinas administrativas','Mantenimiento de pintura coordinación administrativa, dirección, enfermería, bodega de insumos','Cuando se Requiera','Auxiliar de Servicios Generales','N/A','Auxiliar de Servicios Generales'],
            [19,'Aseo y Desinfección Patios y exteriores','Lubricación en marcos de las ventanas y limpieza de vidrios de los salones 2, 3 y 8','Cuando se Requiera','Auxiliar de Servicios Generales','N/A','Auxiliar de Servicios Generales'],
            [20,'Mantenimiento de Estanterías','Mantenimiento de Estanterías','Cuando se Requiera','Auxiliar de Servicios Generales','N/A','Auxiliar de Servicios Generales'],
            [21,'Mantenimiento de Cielo Raso','Mantenimiento de Cielo Raso','Cuando se Requiera',null,'N/A',null],
            [22,'Mantenimiento espacios depotivos.','Control de arvenses y limpieza a escenarios deportivos y zonas de esparcimiento...','Cuando se Requiera','Auxiliar de Servicios Generales','N/A','Auxiliar de Servicios Generales'],
            [23,'Adecuaciones','Adecuación espacios para biblioteca, Adecuación oficina de bienestar Universitario...','Cuando se Requiera','Carlos Alberto Hoyos Ossa','N/A','Carlos Alberto Hoyos Ossa'],
        ];

        $creadas = 0;
        foreach ($acts as [$orden,$actividad,$descripcion,$freq,$responsable,$proveedor,$responsableMostrar]) {
            $data = [
                'actividad' => $actividad,
                'descripcion' => $descripcion,
                'frecuencia' => $mapFrecuencia($freq),
                'fecha_inicio' => '2025-01-01',
                'fecha_final' => '2025-12-31',
                'realizado' => false,
                'proveedor' => $proveedor,
                'responsable' => $responsable ?? ($responsableMostrar ?? 'Servicios Generales'),
                'orden' => $orden,
            ];

            $existente = ActividadMantenimiento::where('orden', $orden)
                ->orWhere('actividad', $actividad)
                ->first();

            if ($existente) {
                $existente->update($data);
            } else {
                ActividadMantenimiento::create($data);
                $creadas++;
            }
        }

        $this->info("Actividades procesadas. Creadas: {$creadas}");
        $this->info('Listo. Puedes verlas en Mantenimiento → Plan de Mantenimiento Preventivo.');
        return 0;
    }
}


