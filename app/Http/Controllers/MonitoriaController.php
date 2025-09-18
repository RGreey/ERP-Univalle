<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Monitoria;
use App\Models\Convocatoria;
use App\Models\ProgramaDependencia;
use App\Models\PeriodoAcademico;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\MonitoriaRequiereAjustesMail;
use Illuminate\Support\Facades\DB;
use App\Models\Seguimiento;
use Carbon\Carbon;
use App\Helpers\ConvocatoriaHelper;

class MonitoriaController extends Controller
{


    /**
     * Display a listing of the monitorias.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Obtener la convocatoria activa usando el helper
        $convocatoria = ConvocatoriaHelper::obtenerConvocatoriaActiva();

        // --- NUEVA LÓGICA PARA CONVOCATORIA ANTERIOR ---
        // Si existe una convocatoria reabierta, esa es la anterior lógica
        $convocatoriaAnterior = Convocatoria::whereNotNull('fechaReapertura')
            ->orderBy('fechaReapertura', 'desc')
            ->first();
        // Si no hay reabierta, usar la última cerrada
        if (!$convocatoriaAnterior) {
            $convocatoriaAnterior = Convocatoria::where('fechaCierre', '<', now())
                ->orderBy('fechaCierre', 'desc')
                ->first();
        }

        // Monitorías de la convocatoria anterior
        $monitoriasPasadas = collect();
        if ($convocatoriaAnterior) {
            $query = Monitoria::with(['convocatoria', 'programadependencia'])
                ->where('convocatoria', $convocatoriaAnterior->id);
            // Si no es admin, mostrar solo las monitorías del usuario
            if (!auth()->user()->hasRole('CooAdmin') && !auth()->user()->hasRole('AuxAdmin')) {
                $query->where('encargado', auth()->id());
            }
            $monitoriasPasadas = $query->get();
        }

        // Monitorías históricas (todas las que no son de la convocatoria activa)
        $monitoriasHistoricas = Monitoria::with(['convocatoria', 'programadependencia'])
            ->when($convocatoria, function($query) use ($convocatoria) {
                return $query->where('convocatoria', '!=', $convocatoria->id);
            })
            ->when(!auth()->user()->hasRole('CooAdmin') && !auth()->user()->hasRole('AuxAdmin'), function($query) {
                return $query->where('encargado', auth()->id());
            })
            ->get();

        // Inicializar variables para las horas
        $horasAprobAdmin = 0;
        $horasAprobDoc = 0;
        $horasAprobInv = 0;
        $horasPendientesAdmin = 0;
        $horasPendientesDoc = 0;
        $horasPendientesInv = 0;

        // Calcular horas solo si hay una convocatoria activa
        if ($convocatoria) {
            // Calcular las horas usadas por monitorías administrativas
            $monitoriasAdmin = Monitoria::where('convocatoria', $convocatoria->id)
                ->where('modalidad', 'administrativo')
                ->get();

            $horasAprobAdmin = $monitoriasAdmin->where('estado', 'aprobado')
                ->sum(function($monitoria) {
                    return $monitoria->vacante * $monitoria->intensidad;
                });

            $horasPendientesAdmin = $monitoriasAdmin->where('estado', '!=', 'aprobado')
                ->sum(function($monitoria) {
                    return $monitoria->vacante * $monitoria->intensidad;
                });

            // Calcular las horas usadas por monitorías de docencia
            $monitoriasDoc = Monitoria::where('convocatoria', $convocatoria->id)
                ->where('modalidad', 'docencia')
                ->get();

            $horasAprobDoc = $monitoriasDoc->where('estado', 'aprobado')
                ->sum(function($monitoria) {
                    return $monitoria->vacante * $monitoria->intensidad;
                });

            $horasPendientesDoc = $monitoriasDoc->where('estado', '!=', 'aprobado')
                ->sum(function($monitoria) {
                    return $monitoria->vacante * $monitoria->intensidad;
                });

            // Calcular las horas usadas por monitorías de investigación
            $monitoriasInv = Monitoria::where('convocatoria', $convocatoria->id)
                ->where('modalidad', 'investigacion')
                ->get();

            $horasAprobInv = $monitoriasInv->where('estado', 'aprobado')
                ->sum(function($monitoria) {
                    return $monitoria->vacante * $monitoria->intensidad;
                });

            $horasPendientesInv = $monitoriasInv->where('estado', '!=', 'aprobado')
                ->sum(function($monitoria) {
                    return $monitoria->vacante * $monitoria->intensidad;
                });
        }

        // Obtener todas las monitorias con sus relaciones (solo activas)
        $query = Monitoria::with(['convocatoria', 'programadependencia'])
            ->when($convocatoria, function($query) use ($convocatoria) {
                return $query->where('convocatoria', $convocatoria->id);
            });

        // Si no es admin, mostrar solo las monitorías del usuario
        if (!auth()->user()->hasRole('CooAdmin') && !auth()->user()->hasRole('AuxAdmin')) {
            $query->where('encargado', auth()->id());
        }

        $monitorias = $query->get();

        // Obtener todas las convocatorias y programas/dependencias
        $convocatorias = Convocatoria::all();
        $programadependencias = ProgramaDependencia::orderBy('nombrePD')->get();

        // Transformar las convocatorias y programas/dependencias en arrays asociativos
        $convocatoriasArray = $convocatorias->pluck('nombre', 'id')->toArray();
        $programadependenciasArray = $programadependencias->pluck('nombrePD', 'id')->toArray();

        // Agregar información adicional a cada monitoria activa
        foreach ($monitorias as $monitoria) {
            $monitoria->nombreConvocatoria = $convocatoriasArray[$monitoria->convocatoria] ?? 'Sin convocatoria';
            $monitoria->nombreProgramaDependencia = $programadependenciasArray[$monitoria->programadependencia] ?? 'Sin programa/dependencia';
            $monitoria->horasTotales = $monitoria->vacante * $monitoria->intensidad;
            $monitoria->isActive = true;
        }

        // Agregar información adicional a cada monitoria pasada
        foreach ($monitoriasPasadas as $monitoria) {
            $monitoria->nombreConvocatoria = $convocatoriasArray[$monitoria->convocatoria] ?? 'Sin convocatoria';
            $monitoria->nombreProgramaDependencia = $programadependenciasArray[$monitoria->programadependencia] ?? 'Sin programa/dependencia';
            $monitoria->horasTotales = $monitoria->vacante * $monitoria->intensidad;
            $monitoria->isActive = false;
        }

        return view('monitoria.crearMonitoria', compact(
            'monitorias',
            'convocatorias', 
            'programadependencias', 
            'convocatoria', 
            'horasAprobAdmin', 
            'horasAprobDoc',
            'horasAprobInv',
            'horasPendientesAdmin',
            'horasPendientesDoc',
            'horasPendientesInv',
            'monitoriasPasadas',
            'monitoriasHistoricas',
            'convocatoriaAnterior'
        ));
    }
        
    

    /**
     * Show the form for creating a new monitoria.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Store a newly created monitoria in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
        $request->validate([
            'nombre' => 'required|string',
            'convocatoria' => 'required|exists:convocatorias,id',
            'programadependencia' => 'required|exists:programadependencia,id',
            'vacante' => 'required|integer',
            'intensidad' => 'required|integer',
                'horario' => 'required|in:diurno,nocturno,mixto',
            'requisitos' => 'required|string',
                'modalidad' => 'required|in:administrativo,docencia,investigacion',
            ]);

            // Obtener el ID del usuario autenticado
            $encargado = auth()->id();

            // Crear la monitoria con el encargado
        $monitoria = Monitoria::create([
                'nombre' => $request->input('nombre'),
                'convocatoria' => $request->input('convocatoria'),
                'programadependencia' => $request->input('programadependencia'),
                'vacante' => $request->input('vacante'),
                'intensidad' => $request->input('intensidad'),
                'horario' => $request->input('horario'),
                'requisitos' => $request->input('requisitos'),
                'modalidad' => $request->input('modalidad'),
                'estado' => 'creado',
                'encargado' => $encargado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Monitoria creada correctamente.',
                'monitoria' => $monitoria
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la monitoria: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function updateEstado(Request $request, $id)
    {
        try {
            $monitoria = Monitoria::findOrFail($id);
            $nuevoEstado = $request->input('estado');

            // Validar que el estado sea uno de los permitidos
            $estadosPermitidos = ['creado', 'autorizado', 'requiere_ajustes', 'aprobado', 'rechazado'];
            if (!in_array($nuevoEstado, $estadosPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado no válido. Los estados permitidos son: creado, autorizado, requiere_ajustes, aprobado, rechazado.'
                ], 400);
            }

            // Solo validar horas si el nuevo estado es 'aprobado'
            if ($nuevoEstado === 'aprobado') {
                // Obtener la convocatoria asociada
                $convocatoria = Convocatoria::findOrFail($monitoria->convocatoria);

                // Calcular las horas de esta monitoria
                $horasSolicitadas = $monitoria->vacante * $monitoria->intensidad;

                // Calcular horas ya aprobadas excluyendo esta monitoria
                $horasUsadas = Monitoria::where('convocatoria', $monitoria->convocatoria)
                    ->where('modalidad', $monitoria->modalidad)
                    ->where('estado', 'aprobado')
                    ->where('id', '!=', $monitoria->id) // Excluir la monitoria actual
                    ->get()
                    ->sum(function($m) {
                        return $m->vacante * $m->intensidad;
                    });

                // Determinar límite de horas según modalidad
                $limiteHoras = $monitoria->modalidad == 'administrativo' 
                    ? $convocatoria->horas_administrativo 
                    : ($monitoria->modalidad == 'docencia' 
                        ? $convocatoria->horas_docencia 
                        : $convocatoria->horas_investigacion);

                // Verificar si hay cupo disponible
                if ($horasUsadas + $horasSolicitadas > $limiteHoras) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede aprobar la monitoria: excede el cupo de horas disponibles.',
                        'detalles' => [
                            'horas_totales' => $limiteHoras,
                            'horas_usadas' => $horasUsadas,
                            'horas_solicitadas' => $horasSolicitadas,
                            'horas_disponibles' => $limiteHoras - $horasUsadas
                        ]
                    ], 400);
                }
            }

            // Si el nuevo estado no es requiere_ajustes, borrar los comentarios
            if ($nuevoEstado !== 'requiere_ajustes') {
                $monitoria->comentarios_ajustes = null;
            }

            // Actualizar comentarios si vienen en la petición
            if ($request->has('comentarios') && $nuevoEstado === 'requiere_ajustes') {
                $monitoria->comentarios_ajustes = $request->input('comentarios');
            }

            // Actualizar el estado
            $monitoria->estado = $nuevoEstado;
            $monitoria->save();

            // Enviar correo si requiere ajustes
            if ($nuevoEstado === 'requiere_ajustes') {
                try {
                    $encargado = \App\Models\User::find($monitoria->encargado);
                    // Destinatarios: encargado y, si corresponde, correo de soporte
                    $destinatarios = collect([]);
                    if ($encargado && $encargado->email) {
                        $destinatarios->push($encargado->email);
                    }
                    // Si quieres notificar a quien creó o administra, agregar aquí
                    foreach ($destinatarios as $correo) {
                        Mail::to($correo)->send(new MonitoriaRequiereAjustesMail($monitoria, $monitoria->comentarios_ajustes, $encargado));
                    }
                } catch (\Throwable $e) {
                    \Log::error('Error enviando correo requiere_ajustes: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'detalles' => [
                    'id' => $monitoria->id,
                    'estado' => $nuevoEstado,
                    'modalidad' => $monitoria->modalidad
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function update(Request $request)
    {
        try {
            $request->validate([
                'monitoria_id' => 'required|exists:monitorias,id',
                'edit_nombre' => 'required|string',
                'edit_convocatoria' => 'required|exists:convocatorias,id',
                'edit_programadependencia' => 'required|exists:programadependencia,id',
                'edit_vacante' => 'required|integer',
                'edit_intensidad' => 'required|integer',
                'edit_horario' => 'required|in:diurno,nocturno,mixto',
                'edit_requisitos' => 'required|string',
                'edit_modalidad' => 'required|in:administrativo,docencia,investigacion',
            ]);

            $monitoria_id = $request->input('monitoria_id');
            $monitoria = Monitoria::findOrFail($monitoria_id);

            // Calcular las nuevas horas solicitadas
            $nuevasHorasSolicitadas = $request->input('edit_vacante') * $request->input('edit_intensidad');
            
            // Si la monitoria está aprobada, validar el cupo de horas
            if ($monitoria->estado === 'aprobado') {
                // Obtener la convocatoria
                $convocatoria = Convocatoria::findOrFail($request->input('edit_convocatoria'));
                
                // Calcular horas ya aprobadas excluyendo esta monitoria
                $horasUsadas = Monitoria::where('convocatoria', $request->input('edit_convocatoria'))
                    ->where('modalidad', $request->input('edit_modalidad'))
                    ->where('estado', 'aprobado')
                    ->where('id', '!=', $monitoria->id) // Excluir la monitoria actual
                    ->get()
                    ->sum(function($m) {
                        return $m->vacante * $m->intensidad;
                    });

                // Determinar límite de horas según modalidad
                $limiteHoras = $request->input('edit_modalidad') == 'administrativo' 
                    ? $convocatoria->horas_administrativo 
                    : $convocatoria->horas_docencia;

                // Verificar si hay cupo disponible
                if ($horasUsadas + $nuevasHorasSolicitadas > $limiteHoras) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede actualizar la monitoria: excedería el cupo de horas disponibles.',
                        'detalles' => [
                            'horas_totales' => $limiteHoras,
                            'horas_usadas' => $horasUsadas,
                            'horas_solicitadas' => $nuevasHorasSolicitadas,
                            'horas_disponibles' => $limiteHoras - $horasUsadas
                        ]
                    ], 400);
                }
            }

            // Actualizar los datos de la monitoria
            $monitoria->nombre = $request->input('edit_nombre');
            $monitoria->convocatoria = $request->input('edit_convocatoria');
            $monitoria->programadependencia = $request->input('edit_programadependencia');
            $monitoria->vacante = $request->input('edit_vacante');
            $monitoria->intensidad = $request->input('edit_intensidad');
            $monitoria->horario = $request->input('edit_horario');
            $monitoria->requisitos = $request->input('edit_requisitos');
            $monitoria->modalidad = $request->input('edit_modalidad');
            $monitoria->save();

            return response()->json([
                'success' => true,
                'message' => 'Monitoria actualizada correctamente.',
                'detalles' => [
                    'id' => $monitoria->id,
                    'estado' => $monitoria->estado,
                    'horas_solicitadas' => $nuevasHorasSolicitadas
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la monitoria: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMonitoria(Request $request)
    {
        $monitoria_id = $request->input('monitoria_id');
        $monitoria = Monitoria::findOrFail($monitoria_id);

        return response()->json(['monitoria' => $monitoria]);
    }

// MonitoriaController.php
public function listarMonitoriasActivas()
{
    // Obtener la convocatoria activa (para postulación de estudiantes) o en período de entrevistas
    $convocatoriaActiva = Convocatoria::where(function($query) {
        $query->where('fechaCierre', '>=', now()) // Convocatoria aún abierta
              ->orWhere(function($subQuery) {
                  $subQuery->where('fechaCierre', '<', now()) // Ya cerrada
                           ->where('fechaEntrevistas', '>=', now()); // Pero en período de entrevistas
              });
    })->orderBy('fechaCierre', 'desc')->first();

    $monitoriasActivas = collect();
    $monitoriasPasadas = collect();

    if ($convocatoriaActiva) {
        // Monitorías de la convocatoria activa SOLO estado aprobado (disponibles para postulación)
        $query = Monitoria::where('convocatoria', $convocatoriaActiva->id)
            ->where('estado', 'aprobado')
            ->with('programadependencia');
            
        if ($convocatoriaActiva->fechaReapertura) {
            $fecha = $convocatoriaActiva->fechaReapertura;
            $query->where(function($q) use ($fecha) {
                $q->where('created_at', '>=', $fecha)
                  ->orWhere('updated_at', '>=', $fecha);
            });
        }
        $monitoriasActivas = $query->get()->map(function ($monitoria) {
            // Definir isActive como true
            $monitoria->isActive = true;
            $programaDependencia = ProgramaDependencia::find($monitoria->programadependencia);
            $monitoria->programadependencia_nombre = $programaDependencia ? $programaDependencia->nombrePD : 'No definido';
            return $monitoria;
        });
    }

    // Monitorías de convocatorias pasadas
    $convocatoriasPasadas = Convocatoria::where('fechaCierre', '<', now())->get();

    $monitoriasPasadas = $convocatoriasPasadas->map(function ($convocatoria) {
        $monitorias = Monitoria::where('convocatoria', $convocatoria->id)
            ->with('programadependencia')
            ->get()
            ->map(function ($monitoria) {
                // Definir isActive como false
                $monitoria->isActive = false;
                $programaDependencia = ProgramaDependencia::find($monitoria->programadependencia);
                $monitoria->programadependencia_nombre = $programaDependencia ? $programaDependencia->nombrePD : 'No definido';
                return $monitoria;
            });

        return [
            'convocatoria' => $convocatoria->nombre,
            'monitorias' => $monitorias,
        ];
    });

    return response()->json([
        'convocatoriaActiva' => $convocatoriaActiva,
        'monitoriasActivas' => $monitoriasActivas,
        'monitoriasPasadas' => $monitoriasPasadas,
    ]);
}
    public function generarPDF()
    {
    // Obtener la convocatoria activa o en período de entrevistas
    $convocatoriaActiva = Convocatoria::where(function($query) {
        $query->where('fechaCierre', '>=', now()) // Convocatoria aún abierta
              ->orWhere(function($subQuery) {
                  $subQuery->where('fechaCierre', '<', now()) // Ya cerrada
                           ->where('fechaEntrevistas', '>=', now()); // Pero en período de entrevistas
              });
    })->orderBy('fechaCierre', 'desc')->first();
    
    // Verificar si hay una convocatoria activa
    if (!$convocatoriaActiva) {
        $pdf = PDF::loadView('monitoria.pdf_sin_convocatoria');
        return $pdf->stream('convocatoria_cerrada.pdf');
    }
    
    // Filtrar monitorias según la fecha de reapertura si existe
    $query = Monitoria::where('convocatoria', $convocatoriaActiva->id)
        ->where('estado', 'aprobado')
        ->with('programadependencia')
        ->orderBy('id', 'desc');
    if ($convocatoriaActiva->fechaReapertura) {
        $fecha = $convocatoriaActiva->fechaReapertura;
        $query->where(function($q) use ($fecha) {
            $q->where('created_at', '>=', $fecha)
              ->orWhere('updated_at', '>=', $fecha);
        });
    }
    $monitorias = $query->get();
    
    // Obtener el período académico asociado con la convocatoria activa
    $periodoAcademico = PeriodoAcademico::find($convocatoriaActiva->periodoAcademico);
    
    // Verificar si se encontró el período académico
    if (!$periodoAcademico) {
        // Puedes manejar el caso cuando no se encuentra el período académico
        $periodoAcademicoNombre = 'Período Académico No Encontrado';
    } else {
        $periodoAcademicoNombre = $periodoAcademico->nombre;
    }
    
    // Pasar datos a la vista del PDF
    $pdf = PDF::loadView('monitoria.pdf', [
        'monitorias' => $monitorias,
        'convocatoriaActiva' => $convocatoriaActiva,
        'periodoAcademicoNombre' => $periodoAcademicoNombre
    ]);
    
    // Mostrar el PDF en el navegador en lugar de descargarlo
    return $pdf->stream('monitorias_convocatoria_' . $convocatoriaActiva->nombre . '.pdf');
    }
    
    public function seguimiento($monitoria_id, Request $request)
    {
        $monitoria = \App\Models\Monitoria::findOrFail($monitoria_id);
        $user = auth()->user();
        
        // Permitir acceso si es monitor o encargado
        $esEncargado = $monitoria->encargado == $user->id;
        
        // Obtener todos los monitores de esta monitoría
        $monitors = \App\Models\Monitor::where('monitoria', $monitoria_id)
            ->with('user')
            ->get();
        
        // Determinar si el usuario es monitor de esta monitoría
        $esMonitor = $monitors->where('user', $user->id)->first();
        
        if (!$esEncargado && !$esMonitor) {
            abort(403, 'No tienes permiso para ver este seguimiento.');
        }
        

        
        // Si es monitor, usar su monitor específico
        if ($esMonitor) {
            $monitor = $esMonitor;
        } else {
            // Si es encargado, determinar qué monitor mostrar
            $monitor_id = $request->get('monitor_id');
            
            if ($monitor_id) {
                // Verificar que el monitor pertenece a esta monitoría
                $monitor = $monitors->where('id', $monitor_id)->first();
                if (!$monitor) {
                    abort(403, 'Monitor no válido para esta monitoría.');
                }
            } else {
                // Si no se especifica monitor_id, usar el primero (compatibilidad)
                $monitor = $monitors->first();
            }
        }
        
        // Si no hay monitores, mostrar mensaje
        if (!$monitor) {
            return view('monitoria.seguimiento', [
                'monitor' => null,
                'esMonitor' => false,
                'esEncargado' => $esEncargado,
                'monitoria' => $monitoria,
                'monitors' => $monitors,
                'actividades' => collect(),
                'metaHoras' => 0,
                'mesActual' => ucfirst(strtolower(now()->locale('es')->monthName)),
                'puedeSubirAsistencia' => false,
                'asistenciaActual' => null,
                'anioActual' => now()->year,
                'desempeno' => null,
                'tieneMultiplesMonitores' => $monitors->count() > 1,
            ]);
        }
        
        $actividades = \App\Models\Seguimiento::where('monitor', $monitor->id)->get();
        $mesActual = strtolower(now()->locale('es')->monthName);
        $metaHoras = 0;
        if ($monitor && $monitor->horas_mensuales) {
            $horasMensuales = is_string($monitor->horas_mensuales) ? json_decode($monitor->horas_mensuales, true) : $monitor->horas_mensuales;
            $metaHoras = $horasMensuales[$mesActual] ?? 0;
        }
        $actividadesMes = $actividades->filter(function($a) use ($mesActual) {
            return strtolower(\Carbon\Carbon::parse($a->fecha_monitoria)->locale('es')->monthName) === $mesActual;
        });
        // Calcular total de horas del mes
        $totalMinutos = 0;
        foreach ($actividadesMes as $actividad) {
            if ($actividad->total_horas) {
                [$h, $m] = array_pad(explode(':', $actividad->total_horas), 2, 0);
                $totalMinutos += ((int)$h) * 60 + ((int)$m);
            }
        }
        $horasCumplidas = floor($totalMinutos / 60);
        $porcentaje = $metaHoras > 0 ? ($horasCumplidas / $metaHoras) * 100 : 0;
        $puedeSubirAsistencia = $porcentaje >= 100;
        // --- NUEVO: Obtener asistencia mensual actual ---
        $asistenciaActual = null;
        if ($monitor) {
            $mesNum = now()->month;
            $anioNum = now()->year;
            // Asegurarse que el monitor_id corresponde al monitor actual
            $asistenciaActual = \App\Models\AsistenciaMonitoria::where('monitor_id', $monitor->id)
                ->where('mes', $mesNum)
                ->where('anio', $anioNum)
                ->orderByDesc('id')
                ->first();
            // Debug para verificar el monitor_id y el path
            \Log::info('MonitoriaController@seguimiento asistenciaActual', [
                'monitor_id' => $monitor->id,
                'mes' => $mesNum,
                'anio' => $anioNum,
                'asistencia_path' => $asistenciaActual ? $asistenciaActual->asistencia_path : null
            ]);
        }
        $anioActual = now()->year;
        // --- NUEVO: Obtener evaluación de desempeño ---
        $desempeno = null;
        if ($monitor) {
            $desempeno = \App\Models\DesempenoMonitor::where('monitor_id', $monitor->id)->latest()->first();
        }
        return view('monitoria.seguimiento', [
            'monitor' => $monitor,
            'esMonitor' => $esMonitor,
            'esEncargado' => $esEncargado,
            'monitoria' => $monitoria,
            'monitors' => $monitors,
            'actividades' => $actividadesMes,
            'metaHoras' => $metaHoras,
            'mesActual' => ucfirst($mesActual),
            'puedeSubirAsistencia' => $puedeSubirAsistencia,
            'asistenciaActual' => $asistenciaActual,
            'anioActual' => $anioActual,
            'desempeno' => $desempeno,
            'tieneMultiplesMonitores' => $monitors->count() > 1,
        ]);
    }

    public function generarPDFSeguimiento($monitor_id, $mes, Request $request)
    {
        try {
            \Log::info('Iniciando generarPDFSeguimiento', [
                'monitor_id' => $monitor_id,
                'mes' => $mes,
                'request_params' => $request->all()
            ]);
            
            // Cargar el monitor usando consultas directas ya que las relaciones no funcionan
            $monitor = \App\Models\Monitor::findOrFail($monitor_id);
            
            // Obtener datos directamente con consultas SQL
            $usuario = \App\Models\User::find($monitor->user);
            $monitoria = \App\Models\Monitoria::find($monitor->monitoria);
            $encargado = $monitoria ? \App\Models\User::find($monitoria->encargado) : null;
            $dependencia = $monitoria ? \App\Models\ProgramaDependencia::find($monitoria->programadependencia) : null;
            
            \Log::info('Monitor cargado con consultas directas', [
                'monitor_id' => $monitor->id,
                'user_name' => $usuario->name ?? 'NO_NAME',
                'user_cedula' => $usuario->cedula ?? 'NO_CEDULA',
                'monitoria_id' => $monitoria->id ?? 'NO_MONITORIA',
                'encargado_name' => $encargado->name ?? 'NO_ENCARGADO',
                'dependencia_name' => $dependencia->nombrePD ?? 'NO_DEPENDENCIA'
            ]);
            
            // Datos del monitor (automáticos)
            $monitorData = [
                'nombre' => $usuario->name ? mb_strtolower($usuario->name, 'UTF-8') : 'nombre no encontrado',
                'cedula' => $usuario->cedula ?? 'cedula no encontrada',
                'plan_academico' => '', // Se quita según solicitud
            ];
            
            // Datos automáticos de la monitoría
            $solicitante = $encargado->name ? mb_strtolower($encargado->name, 'UTF-8') : 'encargado no encontrado';
            $proceso = $dependencia->nombrePD ? mb_strtolower($dependencia->nombrePD, 'UTF-8') : 'dependencia no encontrada';
            
            \Log::info('Datos compilados para PDF', [
                'monitorData' => $monitorData,
                'solicitante' => $solicitante,
                'proceso' => $proceso
            ]);
            
            $periodo = 'Marzo - Junio';
            $sede = 'Caicedonia';
            $firmaDigitalBase64 = $request->input('firmaDigitalBase64', null);
            $firmaSize = $request->input('firmaSize', 70);
            $firmaPos = $request->input('firmaPos', -15);
            
            $actividades = \App\Models\Seguimiento::where('monitor', $monitor_id)
                ->whereMonth('fecha_monitoria', $mes)
                ->orderBy('fecha_monitoria')
                ->get();
                
            \Log::info('Actividades encontradas', [
                'count' => $actividades->count(),
                'actividades' => $actividades->toArray()
            ]);
                
            $pdf = \PDF::loadView('monitoria.seguimiento_pdf', [
                'monitor' => (object)$monitorData,
                'solicitante' => $solicitante,
                'proceso' => $proceso,
                'plan_academico' => $monitorData['plan_academico'],
                'periodo' => $periodo,
                'sede' => $sede,
                'actividades' => $actividades,
                'firmaDigitalBase64' => $firmaDigitalBase64,
                'firmaSize' => $firmaSize,
                'firmaPos' => $firmaPos,
            ]);
            
            \Log::info('PDF generado exitosamente');
            return $pdf->stream('seguimiento_monitoria.pdf');
            
        } catch (\Exception $e) {
            \Log::error('Error en generarPDFSeguimiento', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarSeguimiento(Request $request)
    {
        try {
            // Debug inicial
            \Log::info('Iniciando guardarSeguimiento', [
                'request_all' => $request->all()
            ]);

            $request->validate([
                'monitor_id' => 'required|exists:monitor,id',
                'fecha_monitoria' => 'required|array',
                'hora_ingreso' => 'required|array',
                'hora_salida' => 'required|array',
                'total_horas' => 'required|array',
                'actividad_realizada' => 'required|array',
                'firma_digital' => 'nullable|string',
                'firma_size' => 'nullable|integer',
                'firma_pos' => 'nullable|integer',
            ]);

            $monitorId = $request->input('monitor_id');
            $fechas = $request->input('fecha_monitoria');
            $horasIngreso = $request->input('hora_ingreso');
            $horasSalida = $request->input('hora_salida');
            $totalHoras = $request->input('total_horas');
            $actividades = $request->input('actividad_realizada');
            $firmaDigital = $request->input('firma_digital');
            $firmaSize = $request->input('firma_size');
            $firmaPos = $request->input('firma_pos');

            $user = auth()->user();
            $monitor = \App\Models\Monitor::find($monitorId);
            $monitoria = $monitor ? \App\Models\Monitoria::find($monitor->monitoria) : null;
            $esEncargado = $monitoria && $monitoria->encargado == $user->id;

            // Validar fechas de vinculación y culminación
            if (!$esEncargado && $monitor) {
                $fechaVinculacion = $monitor->fecha_vinculacion;
                $fechaCulminacion = $monitor->fecha_culminacion;

                if ($fechaVinculacion && $fechaCulminacion) {
                    foreach ($fechas as $fecha) {
                        $fechaActividad = \Carbon\Carbon::parse($fecha);
                        $fechaInicio = \Carbon\Carbon::parse($fechaVinculacion);
                        $fechaFin = \Carbon\Carbon::parse($fechaCulminacion);

                        if ($fechaActividad->lt($fechaInicio) || $fechaActividad->gt($fechaFin)) {
                            return redirect()->back()->with('error', 
                                "No puedes registrar actividades fuera del período de vinculación. " .
                                "Período válido: {$fechaInicio->format('d/m/Y')} - {$fechaFin->format('d/m/Y')}. " .
                                "Fecha intentada: {$fechaActividad->format('d/m/Y')}"
                            );
                        }
                    }
                } else {
                    return redirect()->back()->with('error', 
                        'No se han definido las fechas de vinculación y culminación para este monitor. ' .
                        'Por favor, contacta al administrador para configurar las fechas.'
                    );
                }
            }

            // Debug de datos recibidos
            \Log::info('Datos procesados:', [
                'esEncargado' => $esEncargado,
                'firma_digital' => $firmaDigital ? 'presente' : 'ausente',
                'firma_size' => $firmaSize,
                'firma_pos' => $firmaPos,
                'monitor_id' => $monitorId,
                'num_actividades' => count($fechas)
            ]);

            if ($esEncargado) {
                // Si es encargado, actualizar las firmas de las actividades existentes
                $actividadesExistentes = \App\Models\Seguimiento::where('monitor', $monitorId)->get();
                
                \Log::info('Actualizando firmas para encargado', [
                    'num_actividades' => $actividadesExistentes->count()
                ]);

                foreach ($actividadesExistentes as $actividad) {
                    $actividad->firma_digital = $firmaDigital;
                    $actividad->firma_size = $firmaSize;
                    $actividad->firma_pos = $firmaPos;
                    $actividad->save();

                    \Log::info('Actividad actualizada', [
                        'id' => $actividad->id,
                        'firma_presente' => !empty($actividad->firma_digital)
                    ]);
                }

                // Registrar en el historial de documentos cuando se firma
                $mesActual = now()->month;
                $anioActual = now()->year;
                \App\Models\DocumentoMonitor::updateOrCreate(
                    [
                        'monitor_id' => $monitorId,
                        'tipo_documento' => 'seguimiento',
                        'mes' => $mesActual,
                        'anio' => $anioActual
                    ],
                    [
                        'parametros_generacion' => [
                            'nombre' => $monitor->user->name ?? '',
                            'cedula' => $monitor->user->cedula ?? '',
                            'plan_academico' => '',
                            'solicitante' => 'Nombre del solicitante',
                            'proceso' => '',
                            'firma_digital' => $firmaDigital,
                            'firma_size' => $firmaSize,
                            'firma_pos' => $firmaPos
                        ],
                        'estado' => 'firmado',
                        'fecha_generacion' => now()
                    ]
                );

                return redirect()->back()->with('success', '¡Actividades firmadas exitosamente!');
            } else {
                // Si es monitor, guardar nuevas actividades
                \App\Models\Seguimiento::where('monitor', $monitorId)->delete();
                
                for ($i = 0; $i < count($fechas); $i++) {
                    \App\Models\Seguimiento::create([
                        'monitor' => $monitorId,
                        'fecha_monitoria' => $fechas[$i],
                        'hora_ingreso' => $horasIngreso[$i],
                        'hora_salida' => $horasSalida[$i],
                        'total_horas' => $totalHoras[$i],
                        'actividad_realizada' => $actividades[$i],
                        'firma_digital' => null,
                        'firma_size' => null,
                        'firma_pos' => null,
                    ]);
                }

                // Registrar en el historial de documentos cuando se guarda seguimiento
                $mesActual = now()->month;
                $anioActual = now()->year;
                
                // Obtener datos usando consultas directas ya que las relaciones no funcionan
                $monitor = \App\Models\Monitor::find($monitorId);
                $usuario = \App\Models\User::find($monitor->user);
                $monitoria = \App\Models\Monitoria::find($monitor->monitoria);
                $encargado = $monitoria ? \App\Models\User::find($monitoria->encargado) : null;
                $dependencia = $monitoria ? \App\Models\ProgramaDependencia::find($monitoria->programadependencia) : null;
                
                \App\Models\DocumentoMonitor::updateOrCreate(
                    [
                        'monitor_id' => $monitorId,
                        'tipo_documento' => 'seguimiento',
                        'mes' => $mesActual,
                        'anio' => $anioActual
                    ],
                    [
                        'parametros_generacion' => [
                            'nombre' => $usuario->name ? mb_strtolower($usuario->name, 'UTF-8') : '',
                            'cedula' => $usuario->cedula ?? '',
                            'plan_academico' => '', // Se quita según solicitud
                            'solicitante' => $encargado->name ? mb_strtolower($encargado->name, 'UTF-8') : 'Nombre del solicitante',
                            'proceso' => $dependencia->nombrePD ? mb_strtolower($dependencia->nombrePD, 'UTF-8') : ''
                        ],
                        'estado' => 'generado',
                        'fecha_generacion' => now()
                    ]
                );

                return redirect()->back()->with('success', '¡Seguimiento guardado exitosamente!');
            }
        } catch (\Exception $e) {
            \Log::error('Error en guardarSeguimiento: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al guardar el seguimiento: ' . $e->getMessage());
        }
    }

    public function updateComentarios(Request $request, $id)
    {
        try {
            $request->validate([
                'comentarios' => 'required|string|min:1'
            ], [
                'comentarios.required' => 'Debe ingresar un comentario antes de guardar.',
                'comentarios.min' => 'El comentario no puede estar vacío.'
            ]);

            $monitoria = Monitoria::findOrFail($id);
            $monitoria->comentarios_ajustes = $request->input('comentarios');
            $monitoria->save();

            // Enviar correo si la monitoría está en estado requiere_ajustes
            if ($monitoria->estado === 'requiere_ajustes') {
                try {
                    $encargado = \App\Models\User::find($monitoria->encargado);
                    if ($encargado && $encargado->email) {
                        Mail::to($encargado->email)->send(new \App\Mail\MonitoriaRequiereAjustesMail($monitoria, $monitoria->comentarios_ajustes, $encargado));
                    }
                } catch (\Throwable $e) {
                    \Log::error('Error enviando correo tras actualizar comentarios de ajustes: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Comentarios guardados correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los comentarios: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generarPDFDesempeno($monitor_id)
    {
        // Obtener el monitor y su evaluación de desempeño
        $monitor = \App\Models\Monitor::findOrFail($monitor_id);
        $desempeno = \App\Models\DesempenoMonitor::where('monitor_id', $monitor_id)->latest()->first();
        if (!$desempeno) {
            abort(404, 'No se encontró evaluación de desempeño para este monitor.');
        }
        // Calcular puntaje promedio
        $factores = [
            'calidad_trabajo',
            'sigue_instrucciones',
            'responsable_actividad',
            'iniciativa',
            'cumplimiento_horario',
            'relaciones_interpersonales',
            'cooperacion',
            'atencion_usuario',
            'asume_compromisos',
            'maneja_informacion'
        ];
        $suma = 0; $num = 0;
        foreach ($factores as $f) {
            if (isset($desempeno->$f)) {
                $suma += floatval($desempeno->$f);
                $num++;
            }
        }
        $puntaje_promedio = $num ? $suma / $num : 0;
        // Renderizar PDF institucional
        $pdf = \PDF::loadView('monitoria.desempeno_monitor', [
            'monitor' => $monitor,
            'desempeno' => $desempeno,
            'puntaje_promedio' => $puntaje_promedio,
            // Campos individuales
            'periodo_academico' => $desempeno->periodo_academico,
            'programa_academico' => $desempeno->programa_academico,
            'codigo_estudiantil' => $desempeno->codigo_estudiantil,
            'dependencia' => $desempeno->dependencia,
            'apellidos_estudiante' => $desempeno->apellidos_estudiante,
            'nombres_estudiante' => $desempeno->nombres_estudiante,
            'modalidad_monitoria' => $desempeno->modalidad_monitoria,
            'fecha_inicio' => $desempeno->fecha_inicio,
            'fecha_fin' => $desempeno->fecha_fin,
            'evaluador_nombres' => $desempeno->evaluador_nombres,
            'evaluador_apellidos' => $desempeno->evaluador_apellidos,
            'evaluador_identificacion' => $desempeno->evaluador_identificacion,
            'evaluador_cargo' => $desempeno->evaluador_cargo,
            'evaluador_dependencia' => $desempeno->evaluador_dependencia,
            'sugerencias' => $desempeno->sugerencias,
            'fecha_evaluacion' => $desempeno->fecha_evaluacion,
            'firma_evaluador' => $desempeno->firma_evaluador,
            'firma_evaluado' => $desempeno->firma_evaluado,
        ]);
        return $pdf->stream('evaluacion_desempeno_monitor.pdf');
    }

    public function guardarDesempeno(Request $request)
    {
        try {
            $data = $request->all();
            $request->validate([
                'monitor_id' => 'required|exists:monitor,id',
                'periodo_academico' => 'required',
                // El resto de campos pueden ser requeridos según el flujo, pero para permitir firmas independientes, solo los del monitor o evaluador según el caso
            ]);

            // Buscar o crear el registro de desempeño
            $desempeno = \App\Models\DesempenoMonitor::firstOrNew([
                'monitor_id' => $data['monitor_id'],
                'periodo_academico' => $data['periodo_academico'],
            ]);

            // Actualizar solo los campos presentes en la petición
            $campos = [
                'codigo_estudiantil', 'programa_academico', 'apellidos_estudiante', 'nombres_estudiante',
                'modalidad_monitoria', 'dependencia', 'evaluador_identificacion', 'evaluador_apellidos',
                'evaluador_nombres', 'evaluador_cargo', 'evaluador_dependencia', 'fecha_inicio', 'fecha_fin',
                'sugerencias', 'fecha_evaluacion'
            ];
            foreach ($campos as $campo) {
                if (isset($data[$campo])) {
                    $desempeno->$campo = $data[$campo];
                }
            }

            // Factores de calificación
            $factores = [
                'calidad_trabajo', 'sigue_instrucciones', 'responsable_actividad', 'iniciativa',
                'cumplimiento_horario', 'relaciones_interpersonales', 'cooperacion', 'atencion_usuario',
                'asume_compromisos', 'maneja_informacion'
            ];
            foreach ($factores as $f) {
                if (isset($data[$f])) {
                    $desempeno->$f = $data[$f];
                }
            }

            // Firmas (pueden ser nulas)
            if ($request->hasFile('firma_evaluador')) {
                $file = $request->file('firma_evaluador');
                $desempeno->firma_evaluador = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
            } elseif ($request->has('firma_evaluador')) {
                $desempeno->firma_evaluador = $data['firma_evaluador'];
            }
            if ($request->hasFile('firma_evaluado')) {
                $file = $request->file('firma_evaluado');
                $desempeno->firma_evaluado = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
            } elseif ($request->has('firma_evaluado')) {
                $desempeno->firma_evaluado = $data['firma_evaluado'];
            }

            $desempeno->save();

            // Registrar en el historial de documentos cuando se guarda evaluación de desempeño
            if ($desempeno->firma_evaluador && $desempeno->firma_evaluado) {
                \App\Models\DocumentoMonitor::updateOrCreate(
                    [
                        'monitor_id' => $data['monitor_id'],
                        'tipo_documento' => 'evaluacion_desempeno'
                    ],
                    [
                        'parametros_generacion' => [
                            'periodo_academico' => $data['periodo_academico'],
                            'fecha_evaluacion' => $data['fecha_evaluacion'] ?? now()->format('Y-m-d')
                        ],
                        'estado' => 'firmado',
                        'fecha_generacion' => now()
                    ]
                );
            } else {
                \App\Models\DocumentoMonitor::updateOrCreate(
                    [
                        'monitor_id' => $data['monitor_id'],
                        'tipo_documento' => 'evaluacion_desempeno'
                    ],
                    [
                        'parametros_generacion' => [
                            'periodo_academico' => $data['periodo_academico'],
                            'fecha_evaluacion' => $data['fecha_evaluacion'] ?? now()->format('Y-m-d')
                        ],
                        'estado' => 'generado',
                        'fecha_generacion' => now()
                    ]
                );
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function borrarDesempeno(Request $request)
    {
        \Log::info('Intentando borrar desempeño', [
            'request_all' => $request->all(),
            'desempeno_id' => $request->desempeno_id,
        ]);
        if (!$request->filled('desempeno_id')) {
            \Log::warning('No se envió desempeno_id');
            return response()->json(['success' => false, 'message' => 'No se envió el ID de la evaluación.']);
        }
        $desempeno = \App\Models\DesempenoMonitor::find($request->desempeno_id);
        if ($desempeno) {
            \Log::info('Registro encontrado para borrar', ['id' => $desempeno->id]);
            $desempeno->delete();
            \Log::info('Desempeño borrado correctamente', ['id' => $desempeno->id]);
            return response()->json(['success' => true]);
        }
        \Log::warning('No se encontró desempeño para borrar', [
            'desempeno_id' => $request->desempeno_id,
        ]);
        return response()->json(['success' => false, 'message' => 'No se encontró la evaluación para borrar.']);
    }
}


