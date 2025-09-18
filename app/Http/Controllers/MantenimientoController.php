<?php

namespace App\Http\Controllers;

use App\Models\ActividadMantenimiento;
use App\Models\SemanaMantenimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MantenimientoController extends Controller
{
    public function __construct()
    {
        // El middleware de autenticación y permisos se maneja en las rutas
        // No es necesario aplicar middleware adicional aquí
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $actividades = ActividadMantenimiento::orderBy('orden')->get();
        $anioActual = now()->year;
        
        $estadisticas = [
            'total_actividades' => $actividades->count(),
            'realizadas' => $actividades->where('realizado', true)->count(),
            'pendientes' => $actividades->where('realizado', false)->count(),
            'por_frecuencia' => [
                'anual' => $actividades->where('frecuencia', 'anual')->count(),
                'trimestral' => $actividades->where('frecuencia', 'trimestral')->count(),
                'cuatrimestral' => $actividades->where('frecuencia', 'cuatrimestral')->count(),
                'mensual' => $actividades->where('frecuencia', 'mensual')->count(),
                'cuando_se_requiera' => $actividades->where('frecuencia', 'cuando_se_requiera')->count(),
            ]
        ];

        return view('mantenimiento.index', compact('actividades', 'estadisticas', 'anioActual'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('mantenimiento.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'actividad' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'frecuencia' => 'required|in:anual,trimestral,cuatrimestral,mensual,cuando_se_requiera',
            'fecha_inicio' => 'required|date',
            'fecha_final' => 'required|date|after_or_equal:fecha_inicio',
            'proveedor' => 'nullable|string|max:255',
            'responsable' => 'required|string|max:255',
            'orden' => 'nullable|integer|min:0',
        ]);

        $actividad = ActividadMantenimiento::create($request->all());

        // Generar semanas para el año actual
        $actividad->generarSemanas(now()->year);

        return redirect()->route('mantenimiento.index')
            ->with('success', 'Actividad de mantenimiento creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ActividadMantenimiento $actividad)
    {
        $anioActual = now()->year;
        $semanas = $actividad->semanas()->porAnio($anioActual)->orderBy('mes')->orderBy('semana')->get();
        
        // Agrupar semanas por mes
        $semanasPorMes = $semanas->groupBy('mes');
        
        return view('mantenimiento.show', compact('actividad', 'semanasPorMes', 'anioActual'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ActividadMantenimiento $actividad)
    {
        return view('mantenimiento.edit', compact('actividad'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ActividadMantenimiento $actividad)
    {
        $request->validate([
            'actividad' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'frecuencia' => 'required|in:anual,trimestral,cuatrimestral,mensual,cuando_se_requiera',
            'fecha_inicio' => 'required|date',
            'fecha_final' => 'required|date|after_or_equal:fecha_inicio',
            'proveedor' => 'nullable|string|max:255',
            'responsable' => 'required|string|max:255',
            'orden' => 'nullable|integer|min:0',
        ]);

        $actividad->update($request->all());

        return redirect()->route('mantenimiento.index')
            ->with('success', 'Actividad de mantenimiento actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ActividadMantenimiento $actividad)
    {
        $actividad->delete();

        return redirect()->route('mantenimiento.index')
            ->with('success', 'Actividad de mantenimiento eliminada exitosamente.');
    }

    /**
     * Marcar actividad como realizada
     */
    public function marcarRealizada(Request $request, ActividadMantenimiento $actividad)
    {
        $actividad->update(['realizado' => true]);

        return redirect()->back()->with('success', 'Actividad marcada como realizada.');
    }

    /**
     * Marcar actividad como pendiente
     */
    public function marcarPendiente(Request $request, ActividadMantenimiento $actividad)
    {
        $actividad->update(['realizado' => false]);

        return redirect()->back()->with('success', 'Actividad marcada como pendiente.');
    }

    /**
     * Marcar semana como ejecutada
     */
    public function marcarSemanaEjecutada(Request $request, SemanaMantenimiento $semana)
    {
        $request->validate([
            'fecha_ejecucion' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        $semana->marcarEjecutada(
            $request->fecha_ejecucion,
            $request->observaciones
        );

        return redirect()->back()->with('success', 'Semana marcada como ejecutada.');
    }

    /**
     * Marcar semana como pendiente
     */
    public function marcarSemanaPendiente(Request $request, SemanaMantenimiento $semana)
    {
        $semana->marcarPendiente();

        return redirect()->back()->with('success', 'Semana marcada como pendiente.');
    }

    /**
     * Generar semanas para un año específico
     */
    public function generarSemanas(Request $request, ActividadMantenimiento $actividad)
    {
        $anio = $request->input('anio', now()->year);
        $actividad->generarSemanas($anio);

        return redirect()->back()->with('success', "Semanas generadas para el año {$anio}.");
    }

    /**
     * Cargar actividades predeterminadas (las 23 actividades básicas)
     */
    public function cargarActividadesPredeterminadas()
    {
        $actividades = [
            [ 'actividad' => 'Revisar señales de riesgo electrico', 'descripcion' => 'Revisar que todas las señales de riesgo electrico se encuentren en buen estado, de no ser asi cambiarlos', 'frecuencia' => 'anual', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales', 'orden' => 1 ],
            [ 'actividad' => 'Visita técnica e inspección', 'descripcion' => 'Visita tecnica e inspección de seguridad año 2025 a la Sede Regional Caicedonia', 'frecuencia' => 'anual', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => 'Benemerito cuerpo de Bombero', 'responsable' => 'Coordinación Administrativa', 'orden' => 2 ],
            [ 'actividad' => 'Mantenimiento Aires Acondicionados', 'descripcion' => 'Mantenimientos Aires Acondicionador Sede Valle del Cauca y Maria Inmaculada', 'frecuencia' => 'anual', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Coordinación Administrativa', 'orden' => 3 ],
            [ 'actividad' => 'Fumigación', 'descripcion' => 'Fumigar todas los espacios de la universidad', 'frecuencia' => 'anual', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => 'SERVISAM', 'responsable' => 'Coordinación Administrativa', 'orden' => 4 ],
            [ 'actividad' => 'Mantenimiento de Red Eléctrica', 'descripcion' => 'Mantenimiento y revisión de Red Eléctrica', 'frecuencia' => 'anual', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => 'JESUS HERNANDO LUNA ORTIZ', 'responsable' => 'Coordinación Administrativa', 'orden' => 5 ],
            [ 'actividad' => 'Limpieza de Canales', 'descripcion' => 'Limpiar y verificar el estado de las canales en todos los espacios de la universidad.', 'frecuencia' => 'cuatrimestral', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales-DIU', 'orden' => 6 ],
            [ 'actividad' => 'Revisión de Extintores', 'descripcion' => 'Revisión y mantenimiento de extintores', 'frecuencia' => 'trimestral', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => 'Benemerito cuerpo de Bombero', 'responsable' => 'Auxiliar de Servicios Varios/Profesional Salud Ocupacional', 'orden' => 7 ],
            [ 'actividad' => 'Revisión de zonas verdes', 'descripcion' => 'Manejo de arvenses mensualmente (Deshierbar-guadañar) y arreglo de jardines, poda de arboles', 'frecuencia' => 'mensual', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Varios', 'orden' => 8 ],
            [ 'actividad' => 'Recorrido por las instalaciones', 'descripcion' => 'Recorrido por las instalciones verificando goteras, chapas, enchufes y sillas', 'frecuencia' => 'mensual', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales', 'orden' => 9 ],
            [ 'actividad' => 'Mantenimiento de pintura', 'descripcion' => '.Mantenimiento de pintura al área de psicología, entrada de laboratorio y baño principal ...', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales/DIU', 'orden' => 10 ],
            [ 'actividad' => 'Mantenimiento salas de sistemas', 'descripcion' => '.Mantenimienti de pintura sala D', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales', 'orden' => 11 ],
            [ 'actividad' => 'Mantenimiento de fachadas', 'descripcion' => 'Labores de barrido y recogida de los residuos tanto en la zona externa como interna del Campus ...', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales-DIU', 'orden' => 12 ],
            [ 'actividad' => 'Mantenimiento de Baños', 'descripcion' => '. Mantenimiento de pintura soportes de lavamanos Baños MI. Adecuación baño laboratorios. Cambio de perillo y arbol a dos baños de la MI', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales/DIU', 'orden' => 13 ],
            [ 'actividad' => 'Mantenimiento de iluminación', 'descripcion' => 'Cambio de canaletas en los salones 4 y 5 VC. Cambio de cableado y de lamparas ...', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales', 'orden' => 14 ],
            [ 'actividad' => 'Mantenimiento zonas de espacimiento', 'descripcion' => 'Mantenimiento de pintura a los patios ubicados en el segundo pido del edificio María Inmaculada, ...', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales', 'orden' => 15 ],
            [ 'actividad' => 'Mantenimiento portería, cocina', 'descripcion' => 'Mantenimiento portería, cocina', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'DIU', 'orden' => 16 ],
            [ 'actividad' => 'Mantenimiento a las señalizaciones de seguridad', 'descripcion' => 'Demarcación de las escaleras con cinta antideslizante, Demarcación de todas las lineas de seguridad...', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales', 'orden' => 17 ],
            [ 'actividad' => 'Mantenimiento de oficinas administrativas', 'descripcion' => 'Mantenimiento de pintura coordinción administrativa, dirección, enfermería, bodega de insumos', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales', 'orden' => 18 ],
            [ 'actividad' => 'Aseo y Desinfección Patios y exteriores', 'descripcion' => 'Lubricación en marcos de las ventanas y limpieza de vidrios de los salones 2, 3 y 8', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales', 'orden' => 19 ],
            [ 'actividad' => 'Mantenimiento de Estanterías', 'descripcion' => 'Mantenimiento de Estanterías', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales', 'orden' => 20 ],
            [ 'actividad' => 'Mantenimiento de Cielo Raso', 'descripcion' => 'Mantenimiento de Cielo Raso', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Servicios Generales', 'orden' => 21 ],
            [ 'actividad' => 'Mantenimiento espacios depotivos.', 'descripcion' => 'Control de arvenses y limpieza a escenarios deportivos y zonas de esparcimiento, marcación de la cancha, Desyerbe, limpieza de las zonas deportivas y demarcación de la cancha de futbol', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Auxiliar de Servicios Generales', 'orden' => 22 ],
            [ 'actividad' => 'Adecuaciones', 'descripcion' => 'Adecuación espacios para biblioteca, Adecuación oficina de bienestar Universitario. Ampliación de tarima del audito MI., adecuación de soportes en la oficina de suministros', 'frecuencia' => 'cuando_se_requiera', 'fecha_inicio' => '2025-01-01', 'fecha_final' => '2025-12-31', 'proveedor' => null, 'responsable' => 'Carlos Alberto Hoyos Ossa', 'orden' => 23 ],
        ];

        foreach ($actividades as $actividad) {
            $nuevaActividad = ActividadMantenimiento::create($actividad);
            $nuevaActividad->generarSemanas(now()->year);
        }

        return redirect()->route('mantenimiento.index')
            ->with('success', 'Actividades predeterminadas cargadas exitosamente.');
    }

    /**
     * Eliminar todas las actividades y sus semanas asociadas
     */
    public function eliminarTodas()
    {
        // Contar registros antes de eliminar
        $totalSemanas = SemanaMantenimiento::count();
        $totalActividades = ActividadMantenimiento::count();
        
        // Eliminar primero semanas, luego actividades
        SemanaMantenimiento::query()->delete();
        ActividadMantenimiento::query()->delete();

        $mensaje = "Se eliminaron {$totalActividades} actividades y {$totalSemanas} semanas de mantenimiento.";
        
        return redirect()->route('mantenimiento.index')
            ->with('success', $mensaje);
    }

    /**
     * Limpiar solo las semanas de mantenimiento sin afectar las actividades
     */
    public function limpiarSemanas(Request $request)
    {
        $anio = $request->input('anio');
        
        $query = SemanaMantenimiento::query();
        if ($anio) {
            $query->where('anio', $anio);
            $totalEliminadas = $query->count();
            $query->delete();
            
            return redirect()->route('mantenimiento.index')
                ->with('success', "Se eliminaron {$totalEliminadas} semanas del año {$anio}.");
        } else {
            $totalEliminadas = $query->count();
            $query->delete();
            
            return redirect()->route('mantenimiento.index')
                ->with('success', "Se eliminaron {$totalEliminadas} semanas de todos los años.");
        }
    }

    /**
     * Vista tipo Excel para visualizar el plan completo
     */
    public function vistaExcel()
    {
        $actividades = ActividadMantenimiento::orderBy('orden')->get();
        $anioActual = now()->year;
        
        return view('mantenimiento.vista-excel', compact('actividades', 'anioActual'));
    }

    /**
     * Exportar plan de mantenimiento a Excel
     */
    public function exportarExcel()
    {
        $actividades = ActividadMantenimiento::orderBy('orden')->get();
        $anioActual = now()->year;
        
        // Crear nuevo documento Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Configurar título
        $sheet->setCellValue('A1', 'PLAN DE MANTENIMIENTO PREVENTIVO - ' . $anioActual);
        $sheet->mergeCells('A1:Z1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Encabezados principales
        $headers = [
            'A3' => 'Actividad',
            'B3' => 'Descripción',
            'C3' => 'Frecuencia',
            'D3' => 'Fecha Inicio',
            'E3' => 'Fecha Final',
            'F3' => 'Realizado',
            'G3' => 'Proveedor',
            'H3' => 'Responsable'
        ];
        
        // Aplicar encabezados principales
        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }
        
        // Encabezados de semanas (columnas I en adelante)
        $colIndex = 9; // Columna I
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        foreach ($meses as $mes => $nombreMes) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($colLetter . '2', $nombreMes);
            $sheet->mergeCells($colLetter . '2:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 3) . '2');
            $sheet->getStyle($colLetter . '2')->getFont()->setBold(true);
            $sheet->getStyle($colLetter . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('70AD47');
            $sheet->getStyle($colLetter . '2')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($colLetter . '2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Subencabezados de semanas
            for ($semana = 1; $semana <= 4; $semana++) {
                $subColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + $semana - 1);
                $sheet->setCellValue($subColLetter . '3', 'S' . $semana);
                $sheet->getStyle($subColLetter . '3')->getFont()->setBold(true);
                $sheet->getStyle($subColLetter . '3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('A5A5A5');
                $sheet->getStyle($subColLetter . '3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
            
            $colIndex += 4;
        }
        
        // Datos de actividades
        $row = 4;
        foreach ($actividades as $actividad) {
            $sheet->setCellValue('A' . $row, $actividad->actividad);
            $sheet->setCellValue('B' . $row, $actividad->descripcion);
            $sheet->setCellValue('C' . $row, ucfirst($actividad->frecuencia));
            $sheet->setCellValue('D' . $row, \Carbon\Carbon::parse($actividad->fecha_inicio)->format('d/m/Y'));
            $sheet->setCellValue('E' . $row, \Carbon\Carbon::parse($actividad->fecha_final)->format('d/m/Y'));
            $sheet->setCellValue('F' . $row, $actividad->realizado ? 'SÍ' : 'NO');
            $sheet->setCellValue('G' . $row, $actividad->proveedor ?: 'Servicios Generales');
            $sheet->setCellValue('H' . $row, $actividad->responsable);
            
            // Obtener semanas de la actividad
            $semanas = $actividad->semanas()->porAnio($anioActual)->get();
            
            // Marcar semanas ejecutadas
            foreach ($semanas as $semana) {
                $colIndex = 9 + (($semana->mes - 1) * 4) + ($semana->semana - 1);
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                
                if ($semana->ejecutado) {
                    $sheet->setCellValue($colLetter . $row, 'X');
                    $sheet->getStyle($colLetter . $row)->getFont()->setBold(true);
                    $sheet->getStyle($colLetter . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('92D050');
                    $sheet->getStyle($colLetter . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
            }
            
            $row++;
        }
        
        // Ajustar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(25);
        $sheet->getColumnDimension('H')->setWidth(25);
        
        // Ajustar ancho de columnas de semanas
        for ($i = 9; $i <= 56; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setWidth(8);
        }
        
        // Aplicar bordes
        $lastRow = $row - 1;
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(56);
        $sheet->getStyle('A3:' . $lastCol . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // Configurar el writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Generar nombre del archivo
        $filename = 'Plan_Mantenimiento_Preventivo_' . $anioActual . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Configurar headers para descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Guardar archivo
        $writer->save('php://output');
        exit;
    }
}
