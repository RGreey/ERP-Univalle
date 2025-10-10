<?php

namespace App\Http\Controllers\PWA\Restaurante;

use App\Http\Controllers\Controller;
use App\Models\ReporteSubsidio;
use App\Models\User;
use Illuminate\Http\Request;

class ReportesRestauranteController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:Restaurante']);
    }

    public function index(Request $request)
    {
        $items = ReporteSubsidio::with('user')
            ->where('user_id', auth()->id()) // autor del reporte (restaurante)
            ->orderByDesc('created_at')->paginate(12);

        return view('pwa.restaurante.reportes.index', compact('items'));
    }

    public function create()
    {
        return view('pwa.restaurante.reportes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo'        => ['required','string','max:50'], // comportamiento | inasistencia_reiterada | higiene | otro
            'titulo'      => ['nullable','string','max:140'],
            'descripcion' => ['required','string','max:5000'],
            'sede'        => ['nullable','string','max:30'],
            'estudiante_email' => ['nullable','email','max:190'], // opcional, para ligar a estudiante
        ]);

        $data['user_id'] = auth()->id();
        $data['origen']  = 'restaurante';
        $data['estado']  = 'pendiente';

        // si viene email de estudiante, intenta enlazarlo en la descripción
        if (!empty($data['estudiante_email'])) {
            $u = User::where('email', $data['estudiante_email'])->first();
            if ($u) {
                $data['descripcion'] = "[Estudiante: {$u->id} - {$u->name}] ".$data['descripcion'];
            } else {
                $data['descripcion'] = "[Estudiante (email no encontrado): {$data['estudiante_email']}] ".$data['descripcion'];
            }
            unset($data['estudiante_email']);
        }

        $rep = ReporteSubsidio::create($data);

        return redirect()->route('app.restaurante.reportes.show', $rep)->with('success', 'Reporte enviado.');
    }

    public function show(ReporteSubsidio $reporte)
    {
        abort_unless($reporte->user_id === auth()->id(), 403);
        return view('pwa.restaurante.reportes.show', compact('reporte'));
    }
}