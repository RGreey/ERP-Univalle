<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Lugar;
use App\Models\Espacio;
use App\Models\ProgramaDependencia;
use App\Models\Convocatoria;
use App\Models\Monitoria;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function administrativoDashboard()
    {
        // Obtener el año actual
        $currentYear = date('Y');

        // Obtener la cantidad de eventos por mes en el año actual
        $eventosPorMes = Evento::select(
            DB::raw('MONTH(fechaRealizacion) as mes'),
            DB::raw('COUNT(*) as total')
        )
        ->whereYear('fechaRealizacion', $currentYear)
        ->groupBy(DB::raw('MONTH(fechaRealizacion)'))
        ->orderBy(DB::raw('MONTH(fechaRealizacion)'))
        ->get();

    // Ahora unimos con evento_dependencia para traer la cantidad por dependencia
    $eventosPorMesPrograma = DB::table('evento')
        ->join('evento_dependencia', 'evento.id', '=', 'evento_dependencia.evento_id')
        ->join('programadependencia', 'evento_dependencia.programadependencia_id', '=', 'programadependencia.id')
        ->select(
            DB::raw('MONTH(evento.fechaRealizacion) as mes'),
            'evento_dependencia.programadependencia_id as programa_dependencia',
            'programadependencia.nombrePD as nombre_programa_dependencia',
            DB::raw('COUNT(*) as total')
        )
        ->whereYear('evento.fechaRealizacion', $currentYear)
        ->groupBy(
            DB::raw('MONTH(evento.fechaRealizacion)'),
            'evento_dependencia.programadependencia_id',
            'programadependencia.nombrePD'
        )
        ->orderBy(DB::raw('MONTH(evento.fechaRealizacion)'))
        ->get();

        // Otros datos necesarios para el dashboard
        $lugares = Lugar::all();
        $espacios = Espacio::all();
        $programas = ProgramaDependencia::all();

        return view('roles.administrativo.dashboard', compact('eventosPorMes', 'eventosPorMesPrograma', 'lugares', 'espacios', 'programas'));
    }

    public function profesorDashboard()
    {
        // Obtener el año actual
        $currentYear = date('Y');

        // Obtener la cantidad de eventos por mes en el año actual
        $eventosPorMes = Evento::select(
                DB::raw('MONTH(fechaRealizacion) as mes'),
                DB::raw('COUNT(*) as total')
            )
            ->whereYear('fechaRealizacion', $currentYear)
            ->groupBy(DB::raw('MONTH(fechaRealizacion)'))
            ->orderBy(DB::raw('MONTH(fechaRealizacion)'))
            ->get();

        // Ahora unimos con evento_dependencia para traer la cantidad por dependencia
        $eventosPorMesPrograma = DB::table('evento')
            ->join('evento_dependencia', 'evento.id', '=', 'evento_dependencia.evento_id')
            ->join('programadependencia', 'evento_dependencia.programadependencia_id', '=', 'programadependencia.id')
            ->select(
                DB::raw('MONTH(evento.fechaRealizacion) as mes'),
                'evento_dependencia.programadependencia_id as programa_dependencia',
                'programadependencia.nombrePD as nombre_programa_dependencia',
                DB::raw('COUNT(*) as total')
            )
            ->whereYear('evento.fechaRealizacion', $currentYear)
            ->groupBy(
                DB::raw('MONTH(evento.fechaRealizacion)'),
                'evento_dependencia.programadependencia_id',
                'programadependencia.nombrePD'
            )
            ->orderBy(DB::raw('MONTH(evento.fechaRealizacion)'))
            ->get();


        // Otros datos necesarios para el dashboard
        $lugares = Lugar::all();
        $espacios = Espacio::all();
        $programas = ProgramaDependencia::all();

        return view('roles.profesor.dashboard', compact('eventosPorMes', 'eventosPorMesPrograma', 'lugares', 'espacios', 'programas'));
    }

    /**
     * Obtener estadísticas de horas solicitadas vs aceptadas por convocatoria
     */
    public function estadisticasHorasConvocatoria()
    {
        // Obtener la convocatoria más reciente que ya haya comenzado (fecha de apertura <= hoy)
        $convocatoriaReciente = Convocatoria::where('fechaApertura', '<=', now())
            ->orderBy('fechaCierre', 'desc')
            ->first();
        
        if (!$convocatoriaReciente) {
            return response()->json([]);
        }

        $modalidades = ['administrativo', 'docencia', 'investigacion'];
        $data = [];

        foreach ($modalidades as $modalidad) {
            // Obtener todas las monitorías de esta modalidad en la convocatoria
            $monitorias = Monitoria::where('convocatoria', $convocatoriaReciente->id)
                ->where('modalidad', $modalidad)
                ->get();

            // Calcular horas solicitadas (estados: creado, autorizado, requiere_ajustes, pendiente)
            $horasSolicitadas = $monitorias->whereIn('estado', ['creado', 'autorizado', 'requiere_ajustes', 'pendiente'])
                ->sum(function($monitoria) {
                    return $monitoria->vacante * $monitoria->intensidad;
                });

            // Calcular horas aceptadas (solo estado aprobado)
            $horasAceptadas = $monitorias->where('estado', 'aprobado')
                ->sum(function($monitoria) {
                    return $monitoria->vacante * $monitoria->intensidad;
                });

            // Obtener el límite de horas de la convocatoria para esta modalidad
            $limiteHoras = 0;
            switch ($modalidad) {
                case 'administrativo':
                    $limiteHoras = $convocatoriaReciente->horas_administrativo ?? 0;
                    break;
                case 'docencia':
                    $limiteHoras = $convocatoriaReciente->horas_docencia ?? 0;
                    break;
                case 'investigacion':
                    $limiteHoras = $convocatoriaReciente->horas_investigacion ?? 0;
                    break;
            }

            $data[] = [
                'convocatoria' => $convocatoriaReciente->nombre,
                'modalidad' => ucfirst($modalidad),
                'horas_solicitadas' => $horasSolicitadas,
                'horas_aceptadas' => $horasAceptadas,
                'limite_horas' => $limiteHoras,
                'porcentaje_aceptacion' => $limiteHoras > 0 ? round(($horasAceptadas / $limiteHoras) * 100, 1) : 0
            ];
        }

        return response()->json($data);
    }
}

