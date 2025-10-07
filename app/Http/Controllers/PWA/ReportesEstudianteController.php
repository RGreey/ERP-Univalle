<?php

namespace App\Http\Controllers\PWA;

use App\Http\Controllers\Controller;
use App\Models\ReporteSubsidio;
use Illuminate\Http\Request;

class ReportesEstudianteController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:Estudiante']);
    }

    public function index(Request $request)
    {
        $items = ReporteSubsidio::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('pwa.subsidio.reportes.index', compact('items'));
    }

    public function create()
    {
        return view('pwa.subsidio.reportes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo'        => ['required','string','max:50'],
            'titulo'      => ['nullable','string','max:140'],
            'descripcion' => ['required','string','max:5000'],
            'sede'        => ['nullable','string','max:30'],
        ]);

        $data['user_id'] = auth()->id();
        $data['origen']  = 'app';
        $data['estado']  = 'pendiente';

        $rep = ReporteSubsidio::create($data);

        return redirect()->route('app.subsidio.reportes.show', $rep)->with('success','Tu reporte fue enviado. Gracias por informarnos.');
    }

    public function show(ReporteSubsidio $reporte)
    {
        abort_unless($reporte->user_id === auth()->id(), 403);
        return view('pwa.subsidio.reportes.show', compact('reporte'));
    }
}