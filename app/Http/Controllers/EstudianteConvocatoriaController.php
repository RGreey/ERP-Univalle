<?php

namespace App\Http\Controllers;

use App\Models\ConvocatoriaSubsidio;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EstudianteConvocatoriaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:Estudiante']);
    }

    public function index()
    {
        $userId = auth()->id();

        $convocatorias = ConvocatoriaSubsidio::with([
                'periodoAcademico',
                'encuesta',
                // Cargar solo la postulación del estudiante autenticado
                'postulaciones' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                },
            ])
            ->abiertasParaPostulacion()
            ->orderBy('fecha_cierre')
            ->get();

        return view('roles.estudiante.subsidio.convocatorias', compact('convocatorias'));
    }
}