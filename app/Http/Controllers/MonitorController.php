<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seguimiento;
use App\Models\Monitor;
use App\Models\User;
use App\Models\ProgramaDependencia;
use App\Models\Convocatoria;
use App\Models\Monitoria;
use App\Models\AsistenciaMonitoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Auth;
class MonitorController extends Controller
{

    public function store(Request $request)
{
    // Recuperar el ID del monitor
    $monitorId = $request->input('monitor_id');

    // Validar que el monitor exista
    $monitor = Monitor::find($monitorId);

    if (!$monitor) {
        return response()->json(['error' => 'Monitor no encontrado.'], 404);
    }

    $datos = $request->all();

    foreach ($datos['fecha_monitoria'] as $index => $fecha) {
        // Verificar si la actividad ya existe
        $actividadExistente = Seguimiento::where('monitor', $monitorId)
            ->where('fecha_monitoria', $fecha)
            ->where('hora_ingreso', $datos['hora_ingreso'][$index])
            ->where('hora_salida', $datos['hora_salida'][$index])
            ->where('actividad_realizada', $datos['actividad_realizada'][$index])
            ->exists();

        $observacion = isset($datos['observacion_encargado'][$index]) ? $datos['observacion_encargado'][$index] : null;

        if (!$actividadExistente) {
            // Guardar solo si no existe
            Seguimiento::create([
                'monitor' => $monitorId,
                'fecha_monitoria' => $fecha,
                'hora_ingreso' => $datos['hora_ingreso'][$index],
                'hora_salida' => $datos['hora_salida'][$index],
                'total_horas' => $datos['total_horas'][$index],
                'actividad_realizada' => $datos['actividad_realizada'][$index],
                'observacion_encargado' => $observacion,
            ]);
        } else {
            // Si ya existe, puedes actualizar la observación si lo deseas
            Seguimiento::where('monitor', $monitorId)
                ->where('fecha_monitoria', $fecha)
                ->where('hora_ingreso', $datos['hora_ingreso'][$index])
                ->where('hora_salida', $datos['hora_salida'][$index])
                ->where('actividad_realizada', $datos['actividad_realizada'][$index])
                ->update(['observacion_encargado' => $observacion]);
        }
    }

    return response()->json(['success' => true], 200);
}
    public function eliminar($id)
    {
        try {
            $actividad = Seguimiento::findOrFail($id);
            $actividad->delete();
            return response()->json(['message' => 'Actividad eliminada correctamente.'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Actividad no encontrada.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar la actividad.'], 500);
        }
    }

    public function getMonitorId()
    {
        $userId = Auth::id();
        
        $monitor = Monitor::where('user', $userId)->first();
        
        if (!$monitor) {
            return response()->json(['error' => 'No se encontró un monitor asociado a este usuario.'], 404);
        }
        
        return response()->json(['monitor_id' => $monitor->id]);
    }
    public function cargarActividades($monitor_id)
    {
    // Obtener todas las actividades del seguimiento para un monitor específico
    $seguimientos = Seguimiento::where('monitor', $monitor_id)->get();

    // Devolver las actividades en formato JSON
    return response()->json([
        'actividades' => $seguimientos
    ]);
    }
    
    /**
     * Mostrar la vista de gestión de monitores
     */
    public function indexGestionMonitores()
    {
        // Aquí puedes obtener los monitores si lo deseas, pero la vista puede cargar por AJAX
        return view('monitoria.gestionMonitores');
    }

    /**
     * Obtener datos de monitorias de la convocatoria activa para la gestión administrativa
     */
    public function getGestionMonitoresData()
    {
        // Obtener la convocatoria más reciente (activa o recién cerrada para mostrar monitores)
        $convocatoria = Convocatoria::orderBy('fechaCierre', 'desc')->first();
        if (!$convocatoria) {
            return response()->json(['monitorias' => []]);
        }
        
        // Obtener monitorias de la convocatoria más reciente que tengan monitores asignados
        $monitorias = Monitoria::with(['programadependencia'])
            ->where('convocatoria', $convocatoria->id)
            ->whereHas('monitor') // Solo monitorias que tienen monitores asignados
            ->get();
        // Puedes mapear aquí los campos que necesitas para la tabla
        $data = [];
        foreach($monitorias as $m) {
            // Buscar todos los monitores asignados a esta monitoria
            $monitors = Monitor::where('monitoria', $m->id)->with('user')->get();
            
            if ($monitors->count() == 0) {
                continue; // Saltar monitorias sin monitores asignados
            }
            
            foreach($monitors as $monitor) {
                $nombre_monitor = 'Sin asignar';
                if ($monitor) {
                    if (is_object($monitor->user) && isset($monitor->user->name)) {
                        $nombre_monitor = $monitor->user->name;
                    } else if ($monitor->user) {
                        $user = User::find($monitor->user);
                        $nombre_monitor = $user ? $user->name : 'Sin asignar';
                    }
                }
                // Obtener el nombre de la dependencia
                $nombre_dependencia = null;
                if (is_object($m->programadependencia) && isset($m->programadependencia->nombrePD)) {
                    $nombre_dependencia = $m->programadependencia->nombrePD;
                } else if ($m->programadependencia) {
                    $dep = ProgramaDependencia::find($m->programadependencia);
                    $nombre_dependencia = $dep ? $dep->nombrePD : null;
                }
                // Calcular semanas entre fechas y horas totales
                $fecha_vinculacion = $monitor ? $monitor->fecha_vinculacion : null;
                $fecha_culminacion = $monitor ? $monitor->fecha_culminacion : null;
                $horas_semanales = $m->intensidad;
                $horas_totales = null;
                if ($fecha_vinculacion && $fecha_culminacion) {
                    $start = new \DateTime($fecha_vinculacion);
                    $end = new \DateTime($fecha_culminacion);
                    $interval = $start->diff($end);
                    $semanas = floor($interval->days / 7) + 1;
                    $horas_totales = $horas_semanales * $semanas;
                }
                // Incluir el campo horas_mensuales si existe
                $horas_mensuales = $monitor && $monitor->horas_mensuales ? $monitor->horas_mensuales : null;
                // Buscar asistencia del mes y año actual
                $anioActual = date('Y');
                $mesActual = date('n'); // 1-12
                $asistencia = \App\Models\AsistenciaMonitoria::where('monitor_id', $monitor->id)
                    ->where('anio', $anioActual)
                    ->where('mes', $mesActual)
                    ->first();
                $asistencia_path = $asistencia ? $asistencia->asistencia_path : null;

                // Verificar si el seguimiento del mes actual está firmado
                $seguimientoFirmado = false;
                $seguimiento = Seguimiento::where('monitor', $monitor->id)
                    ->whereYear('fecha_monitoria', $anioActual)
                    ->whereMonth('fecha_monitoria', $mesActual)
                    ->first();
                
                if ($seguimiento && !empty($seguimiento->firma_digital)) {
                    $seguimientoFirmado = true;
                }

                // Obtener datos de evaluación de desempeño
                $desempeno = \App\Models\DesempenoMonitor::where('monitor_id', $monitor->id)->latest()->first();
                $firma_evaluador = $desempeno ? $desempeno->firma_evaluador : null;
                $firma_evaluado = $desempeno ? $desempeno->firma_evaluado : null;

                // Obtener historial de documentos
                $documentos = \App\Models\DocumentoMonitor::where('monitor_id', $monitor->id)
                    ->orderBy('fecha_generacion', 'desc')
                    ->get();

                $data[] = [
                    'id' => $m->id,
                    'monitor_id' => $monitor->id,
                    'monitor_elegido' => $nombre_monitor,
                    'nombre' => $m->nombre,
                    'horas_semanales' => $horas_semanales,
                    'horas_totales' => $horas_totales,
                    'fecha_vinculacion' => $fecha_vinculacion,
                    'fecha_culminacion' => $fecha_culminacion,
                    'horas_mensuales' => $horas_mensuales,
                    'programa_dependencia' => $nombre_dependencia,
                    'modalidad' => $m->modalidad,
                    'asistencia_path' => $asistencia_path,
                    'seguimiento_firmado' => $seguimientoFirmado,
                    'firma_evaluador' => $firma_evaluador,
                    'firma_evaluado' => $firma_evaluado,
                    'documentos' => $documentos
                ];
            }
        }

        return response()->json(['monitorias' => $data]);
    }

    /**
     * Guardar o actualizar la información de los monitores y sus horas mensuales
     */
    public function storeGestionMonitores(Request $request)
    {
        $data = $request->input('monitores');
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'No se recibieron datos.']);
        }
        foreach ($data as $monitorData) {
            \Log::info('Datos recibidos para guardar:', $monitorData);
            $monitor = Monitor::find($monitorData['monitor_id']);
            if ($monitor) {
                \Log::info('Monitor encontrado:', ['id' => $monitor->id, 'user' => $monitor->user, 'monitoria' => $monitor->monitoria]);
                $monitor->fecha_vinculacion = $monitorData['fecha_vinculacion'] ?? null;
                $monitor->fecha_culminacion = $monitorData['fecha_culminacion'] ?? null;
                if (isset($monitorData['horas_mensuales']) && is_array($monitorData['horas_mensuales'])) {
                    \Log::info('Horas mensuales a guardar:', $monitorData['horas_mensuales']);
                    $monitor->horas_mensuales = json_encode($monitorData['horas_mensuales']);
                }
                $monitor->save();
                \Log::info('Monitor guardado:', ['id' => $monitor->id, 'horas_mensuales' => $monitor->horas_mensuales]);
            } else {
                \Log::warning('No se encontró el monitor con ID:', ['monitor_id' => $monitorData['monitor_id']]);
            }
        }
        return response()->json(['success' => true]);
    }
    public function guardarObservacion(Request $request, $id)
    {
        $request->validate([
            'observacion_encargado' => 'nullable|string|max:500'
        ]);
        $seguimiento = \App\Models\Seguimiento::findOrFail($id);
        $seguimiento->observacion_encargado = $request->observacion_encargado;
        $seguimiento->save();

        return response()->json(['success' => true]);
    }

    /**
     * Subir archivo de asistencia mensual para monitoría de docencia
     */
    public function subirAsistencia(Request $request)
    {
        \Log::info('subirAsistencia - datos recibidos', [
            'all' => $request->all(),
            'files' => $request->allFiles(),
        ]);
        $request->validate([
            'monitor_id' => 'required|exists:monitor,id',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020',
            'archivo_asistencia' => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);
        $monitorId = $request->monitor_id;
        $mes = $request->mes;
        $anio = $request->anio;
        $file = $request->file('archivo_asistencia');

        \Log::info('subirAsistencia - después de validar', [
            'monitor_id' => $monitorId,
            'mes' => $mes,
            'anio' => $anio,
            'file' => $file ? $file->getClientOriginalName() : null,
        ]);

        // Eliminar archivo anterior si existe para ese mes/monitor/año
        $asistencia = AsistenciaMonitoria::where('monitor_id', $monitorId)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->first();
        \Log::info('subirAsistencia - asistencia encontrada', [
            'asistencia' => $asistencia
        ]);
        if ($asistencia && $asistencia->asistencia_path) {
            \Log::info('subirAsistencia - eliminando archivo anterior', [
                'asistencia_path' => $asistencia->asistencia_path
            ]);
            Storage::disk('public')->delete($asistencia->asistencia_path);
        }

        // Guardar nuevo archivo
        $path = $file->storeAs(
            'asistencias/monitor_' . $monitorId . "/{$anio}_{$mes}",
            uniqid('asistencia_') . '.' . $file->getClientOriginalExtension(),
            'public'
        );
        \Log::info('subirAsistencia - archivo guardado', [
            'path' => $path
        ]);

        if (!$asistencia) {
            $asistencia = new AsistenciaMonitoria();
            $asistencia->monitor_id = $monitorId;
            $asistencia->mes = $mes;
            $asistencia->anio = $anio;
        }
        $asistencia->asistencia_path = $path;
        $asistencia->save();
        \Log::info('subirAsistencia - asistencia guardada', [
            'asistencia' => $asistencia
        ]);

        // Registrar en el historial de documentos
        \App\Models\DocumentoMonitor::updateOrCreate(
            [
                'monitor_id' => $monitorId,
                'tipo_documento' => 'asistencia',
                'mes' => $mes,
                'anio' => $anio
            ],
            [
                'ruta_archivo' => $path,
                'estado' => 'generado',
                'fecha_generacion' => now()
            ]
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Archivo de asistencia subido correctamente.']);
        }
        return back()->with('success', 'Archivo de asistencia subido correctamente.');
    }

    /**
     * Descargar o visualizar archivo de asistencia
     */
    public function verAsistencia($monitor_id, $anio, $mes)
    {
        $asistencia = AsistenciaMonitoria::where('monitor_id', $monitor_id)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->first();
        if (!$asistencia || !$asistencia->asistencia_path) {
            abort(404, 'Archivo de asistencia no encontrado');
        }
        if (!Storage::disk('public')->exists($asistencia->asistencia_path)) {
            abort(404, 'Archivo físico no encontrado');
        }
        return response()->file(storage_path('app/public/' . $asistencia->asistencia_path));
    }
    public function borrarAsistencia($monitor_id, $anio, $mes)
{
    $asistencia = \App\Models\AsistenciaMonitoria::where('monitor_id', $monitor_id)
        ->where('mes', $mes)
        ->where('anio', $anio)
        ->first();

    if (!$asistencia) {
        return response()->json(['success' => false, 'message' => 'No se encontró el archivo de asistencia.'], 404);
    }

    // Eliminar el archivo físico si existe
    if ($asistencia->asistencia_path && \Storage::disk('public')->exists($asistencia->asistencia_path)) {
        \Storage::disk('public')->delete($asistencia->asistencia_path);
    }

    // Eliminar el registro de la base de datos
    $asistencia->delete();

    return response()->json(['success' => true, 'message' => 'Archivo de asistencia eliminado correctamente.']);
}

    /**
     * Descargar histórico completo de monitores
     */
    public function descargarHistorico()
    {
        try {
            // Obtener todos los monitores con sus relaciones
            $monitores = Monitor::with([
                'user:id,name,email',
                'monitoria:id,nombre,modalidad,convocatoria,programadependencia,intensidad',
                'monitoria.convocatoria:id,nombre',
                'monitoria.programadependencia:id,nombrePD'
            ])->get();

            // Crear el contenido del archivo Excel
            $filename = 'historico_monitores_' . date('Y-m-d') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($monitores) {
                $file = fopen('php://output', 'w');
                
                // Encabezados
                fputcsv($file, [
                    'ID Monitor',
                    'Nombre Monitor',
                    'Email',
                    'Monitoría',
                    'Modalidad',
                    'Convocatoria',
                    'Programa/Dependencia',
                    'Fecha Vinculación',
                    'Fecha Culminación',
                    'Horas Semanales (Intensidad)',
                    'Horas Mensuales (JSON)',
                    'Estado',
                    'Fecha Creación'
                ]);

                // Datos
                foreach ($monitores as $monitor) {
                    // Obtener datos del usuario de forma segura
                    $userName = 'N/A';
                    $userEmail = 'N/A';
                    
                    if ($monitor->user && is_object($monitor->user)) {
                        $userName = $monitor->user->name ?? 'N/A';
                        $userEmail = $monitor->user->email ?? 'N/A';
                    } elseif ($monitor->user) {
                        // Si user es solo un ID, buscar el usuario
                        $user = User::find($monitor->user);
                        if ($user) {
                            $userName = $user->name ?? 'N/A';
                            $userEmail = $user->email ?? 'N/A';
                        }
                    }

                    // Obtener datos de monitoría de forma segura
                    $monitoriaNombre = 'N/A';
                    $monitoriaModalidad = 'N/A';
                    $monitoriaIntensidad = 'N/A';
                    $convocatoriaNombre = 'N/A';
                    $programaDependenciaNombre = 'N/A';
                    
                    if ($monitor->monitoria && is_object($monitor->monitoria)) {
                        $monitoriaNombre = $monitor->monitoria->nombre ?? 'N/A';
                        $monitoriaModalidad = $monitor->monitoria->modalidad ?? 'N/A';
                        $monitoriaIntensidad = $monitor->monitoria->intensidad ?? 'N/A';
                        
                        // Obtener convocatoria
                        if ($monitor->monitoria->convocatoria && is_object($monitor->monitoria->convocatoria)) {
                            $convocatoriaNombre = $monitor->monitoria->convocatoria->nombre ?? 'N/A';
                        } elseif ($monitor->monitoria->convocatoria) {
                            $convocatoria = Convocatoria::find($monitor->monitoria->convocatoria);
                            if ($convocatoria) {
                                $convocatoriaNombre = $convocatoria->nombre ?? 'N/A';
                            }
                        }
                        
                        // Obtener programa/dependencia
                        if ($monitor->monitoria->programadependencia && is_object($monitor->monitoria->programadependencia)) {
                            $programaDependenciaNombre = $monitor->monitoria->programadependencia->nombrePD ?? 'N/A';
                        } elseif ($monitor->monitoria->programadependencia) {
                            $programaDependencia = ProgramaDependencia::find($monitor->monitoria->programadependencia);
                            if ($programaDependencia) {
                                $programaDependenciaNombre = $programaDependencia->nombrePD ?? 'N/A';
                            }
                        }
                    } elseif ($monitor->monitoria) {
                        // Si monitoria es solo un ID, buscar la monitoría
                        $monitoria = Monitoria::find($monitor->monitoria);
                        if ($monitoria) {
                            $monitoriaNombre = $monitoria->nombre ?? 'N/A';
                            $monitoriaModalidad = $monitoria->modalidad ?? 'N/A';
                            $monitoriaIntensidad = $monitoria->intensidad ?? 'N/A';
                            
                            // Buscar convocatoria y programa/dependencia
                            if ($monitoria->convocatoria) {
                                $convocatoria = Convocatoria::find($monitoria->convocatoria);
                                if ($convocatoria) {
                                    $convocatoriaNombre = $convocatoria->nombre ?? 'N/A';
                                }
                            }
                            
                            if ($monitoria->programadependencia) {
                                $programaDependencia = ProgramaDependencia::find($monitoria->programadependencia);
                                if ($programaDependencia) {
                                    $programaDependenciaNombre = $programaDependencia->nombrePD ?? 'N/A';
                                }
                            }
                        }
                    }

                    fputcsv($file, [
                        $monitor->id,
                        $userName,
                        $userEmail,
                        $monitoriaNombre,
                        $monitoriaModalidad,
                        $convocatoriaNombre,
                        $programaDependenciaNombre,
                        $monitor->fecha_vinculacion ? $monitor->fecha_vinculacion : 'N/A',
                        $monitor->fecha_culminacion ? $monitor->fecha_culminacion : 'N/A',
                        $monitoriaIntensidad,
                        $monitor->horas_mensuales ? $monitor->horas_mensuales : 'N/A',
                        $monitor->estado ? $monitor->estado : 'N/A',
                        $monitor->created_at ? $monitor->created_at->format('Y-m-d H:i:s') : 'N/A'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Error al generar histórico de monitores: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el archivo del histórico: ' . $e->getMessage()
            ], 500);
        }
    }
}
