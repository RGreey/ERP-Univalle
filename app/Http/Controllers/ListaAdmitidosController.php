<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Monitor;
use App\Models\Monitoria;
use App\Models\Convocatoria;
use App\Models\User;
use App\Models\ProgramaDependencia;
use PDF;

class ListaAdmitidosController extends Controller
{
    public function index()
    {
        // Obtener monitores activos con sus datos usando SQL joins
        $monitores = Monitor::select(
                'monitor.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.cedula as user_cedula',
                'monitorias.nombre as monitoria_nombre',
                'monitorias.intensidad as monitoria_intensidad',
                'programadependencia.nombrePD as dependencia_nombre',
                'convocatorias.nombre as convocatoria_nombre',
                'periodoacademico.nombre as periodo_academico_nombre'
            )
            ->join('users', 'monitor.user', '=', 'users.id')
            ->join('monitorias', 'monitor.monitoria', '=', 'monitorias.id')
            ->join('programadependencia', 'monitorias.programadependencia', '=', 'programadependencia.id')
            ->join('convocatorias', 'monitorias.convocatoria', '=', 'convocatorias.id')
            ->join('periodoacademico', 'convocatorias.periodoAcademico', '=', 'periodoacademico.id')
            ->where('monitor.estado', 'activo')
            ->get();

        return view('monitoria.lista-admitidos', compact('monitores'));
    }

    public function actualizarCedulas(Request $request)
    {
        $request->validate([
            'cedulas' => 'required|array',
            'cedulas.*.monitor_id' => 'required|exists:monitor,id',
            'cedulas.*.cedula' => 'nullable|string|max:20'
        ]);

        foreach ($request->cedulas as $data) {
            $monitor = Monitor::find($data['monitor_id']);
            if ($monitor && $monitor->user) {
                $user = User::find($monitor->user);
                if ($user) {
                    $user->cedula = $data['cedula'] ?? null;
                    $user->save();
                }
            }
        }

        return redirect()->back()->with('success', 'Cédulas actualizadas correctamente.');
    }

    public function preview()
    {
        // Obtener monitores activos con sus datos usando SQL joins
        $monitores = Monitor::select(
                'monitor.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.cedula as user_cedula',
                'monitorias.nombre as monitoria_nombre',
                'monitorias.intensidad as monitoria_intensidad',
                'programadependencia.nombrePD as dependencia_nombre',
                'convocatorias.nombre as convocatoria_nombre',
                'convocatorias.id as convocatoria_id',
                'periodoacademico.nombre as periodo_academico_nombre'
            )
            ->join('users', 'monitor.user', '=', 'users.id')
            ->join('monitorias', 'monitor.monitoria', '=', 'monitorias.id')
            ->join('programadependencia', 'monitorias.programadependencia', '=', 'programadependencia.id')
            ->join('convocatorias', 'monitorias.convocatoria', '=', 'convocatorias.id')
            ->join('periodoacademico', 'convocatorias.periodoAcademico', '=', 'periodoacademico.id')
            ->where('monitor.estado', 'activo')
            ->get();

        $admitidos = [];
        $periodos_academicos = [];
        
        foreach ($monitores as $monitor) {
            $admitidos[] = [
                'dependencia' => $monitor->dependencia_nombre ?? 'N/A',
                'horas_semana' => $monitor->monitoria_intensidad ?? 'N/A',
                'vacante' => 1, // Siempre 1 por monitor
                'cc_monitor' => $monitor->user_cedula ?? 'N/A',
                'fecha_inicio' => $monitor->fecha_vinculacion ? 
                    date('d/m/Y', strtotime($monitor->fecha_vinculacion)) : 'N/A'
            ];
            
            // Recolectar periodos académicos únicos
            if ($monitor->periodo_academico_nombre) {
                $periodos_academicos[] = $monitor->periodo_academico_nombre;
            }
        }

        // Ordenar por dependencia
        usort($admitidos, function($a, $b) {
            return strcmp($a['dependencia'], $b['dependencia']);
        });

        // Obtener el periodo académico más común o el primero
        $periodo_principal = !empty($periodos_academicos) ? $periodos_academicos[0] : '2025-I';
        
        // Crear objeto convocatoria con el periodo académico
        $convocatoria = (object) [
            'nombre' => 'Convocatoria Monitorías',
            'periodo_academico_nombre' => $periodo_principal
        ];

        $fecha_generacion = now()->format('d/m/Y H:i:s');

        return view('monitoria.lista-admitidos-preview', compact('admitidos', 'convocatoria', 'fecha_generacion'));
    }

    public function generarPDF()
    {
        // Obtener monitores activos con sus datos usando SQL joins
        $monitores = Monitor::select(
                'monitor.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.cedula as user_cedula',
                'monitorias.nombre as monitoria_nombre',
                'monitorias.intensidad as monitoria_intensidad',
                'programadependencia.nombrePD as dependencia_nombre',
                'convocatorias.nombre as convocatoria_nombre',
                'convocatorias.id as convocatoria_id',
                'periodoacademico.nombre as periodo_academico_nombre'
            )
            ->join('users', 'monitor.user', '=', 'users.id')
            ->join('monitorias', 'monitor.monitoria', '=', 'monitorias.id')
            ->join('programadependencia', 'monitorias.programadependencia', '=', 'programadependencia.id')
            ->join('convocatorias', 'monitorias.convocatoria', '=', 'convocatorias.id')
            ->join('periodoacademico', 'convocatorias.periodoAcademico', '=', 'periodoacademico.id')
            ->where('monitor.estado', 'activo')
            ->get();

        $admitidos = [];
        $periodos_academicos = [];
        
        foreach ($monitores as $monitor) {
            $admitidos[] = [
                'dependencia' => $monitor->dependencia_nombre ?? 'N/A',
                'horas_semana' => $monitor->monitoria_intensidad ?? 'N/A',
                'vacante' => 1, // Siempre 1 por monitor
                'cc_monitor' => $monitor->user_cedula ?? 'N/A',
                'fecha_inicio' => $monitor->fecha_vinculacion ? 
                    date('d/m/Y', strtotime($monitor->fecha_vinculacion)) : 'N/A'
            ];
            
            // Recolectar periodos académicos únicos
            if ($monitor->periodo_academico_nombre) {
                $periodos_academicos[] = $monitor->periodo_academico_nombre;
            }
        }

        // Ordenar por dependencia
        usort($admitidos, function($a, $b) {
            return strcmp($a['dependencia'], $b['dependencia']);
        });

        // Obtener el periodo académico más común o el primero
        $periodo_principal = !empty($periodos_academicos) ? $periodos_academicos[0] : '2025-I';
        
        // Crear objeto convocatoria con el periodo académico
        $convocatoria = (object) [
            'nombre' => 'Convocatoria Monitorías',
            'periodo_academico_nombre' => $periodo_principal
        ];

        $pdf = PDF::loadView('monitoria.lista-admitidos-pdf', compact('admitidos', 'convocatoria'));
        
        return $pdf->download('lista_admitidos_monitorias.pdf');
    }
}
