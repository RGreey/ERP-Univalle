<?php

namespace App\Http\Controllers;

use App\Models\ReporteSubsidio;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminReportesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:AdminBienestar']);
    }

    public function index(Request $request)
    {
        $q     = $request->input('q');
        $estado= $request->input('estado');
        $tipo  = $request->input('tipo');
        $sede  = $request->input('sede');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        $items = ReporteSubsidio::with('user')
            ->when($q, fn($qq)=> $qq->where(function($w) use ($q){
                $w->where('titulo','like',"%$q%")
                    ->orWhere('descripcion','like',"%$q%");
            }))
            ->when($estado, fn($qq)=> $qq->where('estado',$estado))
            ->when($tipo, fn($qq)=> $qq->where('tipo',$tipo))
            ->when($sede, fn($qq)=> $qq->where('sede',$sede))
            ->when($desde, fn($qq)=> $qq->whereDate('created_at','>=', $desde))
            ->when($hasta, fn($qq)=> $qq->whereDate('created_at','<=', $hasta))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        return view('roles.adminbienestar.reportes.index', compact('items','q','estado','tipo','sede','desde','hasta'));
    }

    public function show(ReporteSubsidio $reporte)
    {
        $reporte->load('user');
        return view('roles.adminbienestar.reportes.show', compact('reporte'));
    }

    public function updateEstado(Request $request, ReporteSubsidio $reporte)
    {
        $data = $request->validate([
            'estado'          => ['required','in:pendiente,en_proceso,resuelto,archivado'],
            'admin_respuesta' => ['nullable','string','max:5000'],
        ]);

        $reporte->estado = $data['estado'];
        if (!empty($data['admin_respuesta'])) {
            $reporte->admin_respuesta = $data['admin_respuesta'];
            if ($reporte->estado === 'resuelto' && !$reporte->respondido_en) {
                $reporte->respondido_en = now();
            }
        }
        $reporte->save();

        return back()->with('success','Estado actualizado.');
    }
}