<?php

namespace App\Http\Controllers\PWA\Restaurantes;

use App\Http\Controllers\Controller;
use App\Models\ConvocatoriaSubsidio;
use Illuminate\Http\Request;

class RestaurantesDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:Restaurante']);
    }

    private function allowedSedes(): array
    {
        return method_exists(auth()->user(),'restaurantes')
            ? auth()->user()->restaurantes()->orderBy('codigo')->pluck('codigo')->all()
            : [];
    }

    public function index(Request $request)
    {
        $sedes = $this->allowedSedes();
        $convocatorias = ConvocatoriaSubsidio::orderByDesc('created_at')
            ->get(['id','nombre','fecha_inicio_beneficio','fecha_fin_beneficio']);

        $ctx = [
            'sede'          => session('restaurante_codigo'),
            'convocatoria'  => session('restaurante_convocatoria_id'),
        ];

        return view('pwa.restaurantes.dashboard', compact('sedes','convocatorias','ctx'));
    }

    public function setContext(Request $request)
    {
        $sedes = $this->allowedSedes();
        $data = $request->validate([
            'sede' => ['nullable','string','max:50'],
            'convocatoria_id' => ['nullable','integer','exists:convocatorias_subsidio,id'],
        ]);

        if (!empty($data['sede'])) {
            abort_unless(in_array($data['sede'],$sedes,true), 403);
            session(['restaurante_codigo'=>$data['sede']]);
        } else {
            session()->forget('restaurante_codigo');
        }

        if (!empty($data['convocatoria_id'])) {
            session(['restaurante_convocatoria_id'=>$data['convocatoria_id']]);
        } else {
            session()->forget('restaurante_convocatoria_id');
        }

        return redirect()->route('restaurantes.dashboard')->with('success','Contexto actualizado.');
    }
}