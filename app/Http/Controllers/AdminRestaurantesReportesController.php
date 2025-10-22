<?php

namespace App\Http\Controllers;

use App\Models\CupoAsignacion;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminRestaurantesReportesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:AdminBienestar']);
    }

    public function asistencias(Request $request)
    {
        $desde = Carbon::parse($request->input('desde', now()->startOfWeek()->toDateString()))->startOfDay();
        $hasta = Carbon::parse($request->input('hasta', now()->toDateString()))->endOfDay();
        $conv  = $request->input('convocatoria_id');

        $query = CupoAsignacion::query()
            ->with('cupo')
            ->whereHas('cupo', function($q) use ($desde,$hasta,$conv){
                $q->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);
                if ($conv) $q->where('convocatoria_id',$conv);
            });

        // Build counts grouped
        $raw = $query->get()->groupBy(fn($a)=>$a->cupo?->sede)->map(function($grp){
            return [
                'total'       => $grp->count(),
                'pendiente'   => $grp->where('asistencia_estado','pendiente')->count(),
                'asistio'     => $grp->where('asistencia_estado','asistio')->count(),
                'cancelado'   => $grp->where('asistencia_estado','cancelado')->count(),
                'no_show'     => $grp->where('asistencia_estado','no_show')->count(),
                'inasistencia'=> $grp->where('asistencia_estado','inasistencia')->count(), // por si existiera
            ];
        });

        return view('roles.adminbienestar.restaurantes.reportes.asistencias', [
            'desde'=>$desde,'hasta'=>$hasta,'data'=>$raw,'convocatoria_id'=>$conv,
        ]);
    }
}