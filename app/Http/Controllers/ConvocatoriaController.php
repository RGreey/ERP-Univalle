<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PeriodoAcademico;
use App\Models\Convocatoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConvocatoriaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'periodoAcademico' => 'required|exists:periodoacademico,id',
            'fechaApertura' => 'required|date',
            'fechaCierre' => 'required|date|after:fechaApertura',
            'fechaEntrevistas' => 'required|date|after:fechaCierre',
            'horas_administrativo' => 'required|integer|min:1',
            'horas_docencia' => 'required|integer|min:1',
            'horas_investigacion' => 'required|integer|min:1'
        ]);

        // Verificar si ya existe una convocatoria para el mismo período académico
        $convocatoriaExistente = Convocatoria::where('periodoAcademico', $request->periodoAcademico)->first();
        if ($convocatoriaExistente) {
            return redirect()->back()->with('error', 'Ya existe una convocatoria para este período académico.');
        }

        try {
            Convocatoria::create([
                'nombre' => $request->nombre,
                'periodoAcademico' => $request->periodoAcademico,
                'fechaApertura' => $request->fechaApertura,
                'fechaCierre' => $request->fechaCierre,
                'fechaEntrevistas' => $request->fechaEntrevistas,
                'horas_administrativo' => $request->horas_administrativo,
                'horas_docencia' => $request->horas_docencia,
                'horas_investigacion' => $request->horas_investigacion
            ]);

            return redirect()->back()->with('success', 'Convocatoria creada exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al crear la convocatoria: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $year = date('Y');
        
        // Obtener los períodos académicos del año actual y futuros
        $periodosAcademicos = PeriodoAcademico::whereRaw("LEFT(nombre, 4) >= ?", [$year])->get();

        // Obtener todas las convocatorias con el nombre del período
        $convocatorias = DB::table('convocatorias')
            ->join('periodoacademico', 'convocatorias.periodoAcademico', '=', 'periodoacademico.id')
            ->select('convocatorias.*', 'periodoacademico.nombre as nombrePeriodo')
            ->orderBy('convocatorias.fechaApertura', 'desc')
            ->get();

        // Obtener convocatoria activa
        $convocatoria = $this->getConvocatoriaActiva();

        // Inicializar variables por si no hay convocatoria activa
        $horasAprobAdmin = 0;
        $horasAprobDoc = 0;

        if ($convocatoria) {
            $horasDisponibles = $this->getHorasDisponibles($convocatoria->id);
            $horasAprobAdmin = $horasDisponibles['administrativo']['utilizadas'];
            $horasAprobDoc = $horasDisponibles['docencia']['utilizadas'];
        }

        // Calcular la convocatoria anterior inmediata (la de fechaCierre más reciente pero menor a hoy)
        $convocatoriaAnterior = collect($convocatorias)
            ->filter(function($c) {
                return \Carbon\Carbon::parse($c->fechaCierre)->lt(now());
            })
            ->sortByDesc(function($c) {
                return \Carbon\Carbon::parse($c->fechaCierre);
            })
            ->first();

        return view('monitoria.crearConvocatoria', compact(
            'periodosAcademicos', 
            'convocatorias', 
            'convocatoria', 
            'horasAprobAdmin', 
            'horasAprobDoc',
            'convocatoriaAnterior'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'periodoAcademico' => 'required|exists:periodoacademico,id',
            'fechaApertura' => 'required|date',
            'fechaCierre' => 'required|date|after:fechaApertura',
            'fechaEntrevistas' => 'required|date|after:fechaCierre',
            'horas_administrativo' => 'required|integer|min:1',
            'horas_docencia' => 'required|integer|min:1',
            'horas_investigacion' => 'required|integer|min:1'
        ]);

        try {
            $convocatoria = Convocatoria::findOrFail($id);

            // Verificar si el período académico ha cambiado y si ya existe otra convocatoria para ese período
            if ($convocatoria->periodoAcademico != $request->periodoAcademico) {
                $convocatoriaExistente = Convocatoria::where('periodoAcademico', $request->periodoAcademico)
                    ->where('id', '!=', $id)
                    ->first();
                if ($convocatoriaExistente) {
                    return redirect()->back()->with('error', 'Ya existe una convocatoria para este período académico.');
                }
            }

            $convocatoria->update([
                'nombre' => $request->nombre,
                'periodoAcademico' => $request->periodoAcademico,
                'fechaApertura' => $request->fechaApertura,
                'fechaCierre' => $request->fechaCierre,
                'fechaEntrevistas' => $request->fechaEntrevistas,
                'horas_administrativo' => $request->horas_administrativo,
                'horas_docencia' => $request->horas_docencia,
                'horas_investigacion' => $request->horas_investigacion
            ]);

            return redirect()->back()->with('success', 'Convocatoria actualizada correctamente');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al actualizar la convocatoria.');
        }
    }

    public function destroy($id)
    {
        try {
            $convocatoria = Convocatoria::findOrFail($id);
            $convocatoria->delete();
            return response()->json(['message' => 'Convocatoria eliminada exitosamente']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar la convocatoria: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener la convocatoria activa (la que está en curso hoy)
     *
     * @return Convocatoria|null
     */
    public function getConvocatoriaActiva()
    {
        return Convocatoria::where('fechaApertura', '<=', now())
            ->where('fechaCierre', '>=', now())
            ->orderBy('fechaApertura', 'desc')
            ->first();
    }

    /**
     * Obtener las horas disponibles para una convocatoria
     *
     * @param int $convocatoria_id
     * @return array
     */
    public function getHorasDisponibles($convocatoria_id)
    {
        $convocatoria = Convocatoria::findOrFail($convocatoria_id);
        
        // Obtener las horas ya utilizadas
        $horasUtilizadas = $this->getHorasUtilizadas($convocatoria_id);

        return [
            'administrativo' => [
                'total' => $convocatoria->horas_administrativo,
                'utilizadas' => $horasUtilizadas['administrativo'],
                'disponibles' => $convocatoria->horas_administrativo - $horasUtilizadas['administrativo']
            ],
            'docencia' => [
                'total' => $convocatoria->horas_docencia,
                'utilizadas' => $horasUtilizadas['docencia'],
                'disponibles' => $convocatoria->horas_docencia - $horasUtilizadas['docencia']
            ],
            'investigacion' => [
                'total' => $convocatoria->horas_investigacion,
                'utilizadas' => $horasUtilizadas['investigacion'],
                'disponibles' => $convocatoria->horas_investigacion - $horasUtilizadas['investigacion']
            ]
        ];
    }

    /**
     * Calcular las horas utilizadas en monitorias aprobadas
     *
     * @param int $convocatoria_id
     * @return array
     */
    private function getHorasUtilizadas($convocatoria_id)
    {
        // Determinar el nombre de la columna
        $columnaConvocatoria = Schema::hasColumn('monitorias', 'convocatoria_id') 
            ? 'convocatoria_id' 
            : 'convocatoria';

        $monitorias = \App\Models\Monitoria::where($columnaConvocatoria, $convocatoria_id)
            ->where('estado', 'aprobado')
            ->get();

        $horasAdmin = 0;
        $horasDoc = 0;
        $horasInv = 0;

        foreach ($monitorias as $monitoria) {
            $horasTotales = $monitoria->vacante * $monitoria->intensidad;
            
            if ($monitoria->modalidad === 'administrativo') {
                $horasAdmin += $horasTotales;
            } else if ($monitoria->modalidad === 'docencia') {
                $horasDoc += $horasTotales;
            } else if ($monitoria->modalidad === 'investigacion') {
                $horasInv += $horasTotales;
            }
        }

        return [
            'administrativo' => $horasAdmin,
            'docencia' => $horasDoc,
            'investigacion' => $horasInv
        ];
    }

    /**
     * Verificar si hay horas disponibles para una nueva monitoria
     *
     * @param int $convocatoria_id
     * @param string $modalidad
     * @param int $horas_solicitadas
     * @return bool
     */
    public function verificarDisponibilidadHoras($convocatoria_id, $modalidad, $horas_solicitadas)
    {
        $horasDisponibles = $this->getHorasDisponibles($convocatoria_id);
        
        if ($modalidad === 'administrativo') {
            return $horasDisponibles['administrativo']['disponibles'] >= $horas_solicitadas;
        } else if ($modalidad === 'docencia') {
            return $horasDisponibles['docencia']['disponibles'] >= $horas_solicitadas;
        } else if ($modalidad === 'investigacion') {
            return $horasDisponibles['investigacion']['disponibles'] >= $horas_solicitadas;
        }

        return false;
    }

    public function show($id)
    {
        $convocatoria = Convocatoria::find($id);
        if (!$convocatoria) {
            return redirect()->route('convocatoria.index')->with('error', 'Convocatoria no encontrada');
        }
    
        // Calcular las horas totales de la convocatoria
        $totalHoras = $convocatoria->monitorias->sum('intensidad'); // Asumiendo que la columna 'intensidad' es la que contiene las horas de cada monitoria
    
        // Calcular las horas aprobadas (monitorias con estado 'aprobada')
        $horasAprobadas = $convocatoria->monitorias->where('estado', 'aprobada')->sum('intensidad');
    
        // Calcular las horas disponibles
        $horasDisponibles = $totalHoras - $horasAprobadas;
    
        return view('convocatorias.show', compact('convocatoria', 'totalHoras', 'horasAprobadas', 'horasDisponibles'));
    }

    /**
     * Reabrir una convocatoria cerrada actualizando la fecha de cierre
     */
    public function reabrir(Request $request, $id)
    {
        $request->validate([
            'nueva_fecha_cierre' => 'required|date|after:today',
        ]);
        $convocatoria = Convocatoria::findOrFail($id);
        // Solo permitir si la convocatoria ya está cerrada
        if ($convocatoria->fechaCierre >= now()) {
            return redirect()->back()->with('error', 'La convocatoria aún está activa.');
        }
        // Buscar la convocatoria anterior inmediata
        $convocatoriaAnterior = Convocatoria::where('fechaCierre', '<', now())
            ->orderBy('fechaCierre', 'desc')
            ->first();
        if (!$convocatoriaAnterior || $convocatoriaAnterior->id != $convocatoria->id) {
            return redirect()->back()->with('error', 'Solo puedes reabrir la convocatoria inmediatamente anterior a la actual.');
        }
        $convocatoria->fechaCierre = $request->nueva_fecha_cierre;
        $convocatoria->fechaReapertura = now();
        $convocatoria->save();
        return redirect()->back()->with('success', 'Convocatoria reabierta exitosamente.');
    }

    /**
     * Retorna estadísticas de monitorías: número de monitores y horas por modalidad
     * Solo para la convocatoria activa, sin acumulados
     */
    public function estadisticasMonitorias()
    {
        // Obtener todas las convocatorias
        $convocatorias = \App\Models\Convocatoria::all();
        $modalidades = ['administrativo', 'docencia', 'investigacion'];
        $data = [];

        foreach ($convocatorias as $convocatoria) {
            foreach ($modalidades as $mod) {
                $monitorias = \App\Models\Monitoria::where('convocatoria', $convocatoria->id)
                    ->where('modalidad', $mod)
                    ->where('estado', 'aprobado')
                    ->pluck('id');
                $numMonitores = 0;
                if ($monitorias->count() > 0) {
                    $numMonitores = \App\Models\Monitor::whereIn('monitoria', $monitorias)
                        ->whereNotNull('user')
                        ->distinct('user')
                        ->count('user');
                }
                $horas = \App\Models\Monitoria::whereIn('id', $monitorias)
                    ->sum(DB::raw('vacante * intensidad'));
                $data[] = [
                    'convocatoria' => $convocatoria->nombre,
                    'modalidad' => ucfirst($mod),
                    'monitores' => $numMonitores,
                    'horas' => $horas,
                ];
            }
        }
        return response()->json($data);
    }
}
