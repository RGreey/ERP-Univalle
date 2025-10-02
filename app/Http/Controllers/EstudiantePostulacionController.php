<?php

namespace App\Http\Controllers;

use App\Models\PostulacionSubsidio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EstudiantePostulacionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:Estudiante']);
    }

    public function index()
    {
        $postulaciones = PostulacionSubsidio::with(['convocatoria.periodoAcademico','respuestas.pregunta'])
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('roles.estudiante.subsidio.mis_postulaciones', compact('postulaciones'));
    }

    public function show(PostulacionSubsidio $postulacion)
    {
        abort_unless($postulacion->user_id === auth()->id(), 403);
        // Cargar filas/columnas para la matriz
        $postulacion->load([
            'convocatoria.periodoAcademico',
            'respuestas.pregunta.filas',
            'respuestas.pregunta.columnas',
        ]);
        return view('roles.estudiante.subsidio.detalle_postulacion', compact('postulacion'));
    }

    public function download(PostulacionSubsidio $postulacion)
    {
        abort_unless($postulacion->user_id === auth()->id(), 403);
        abort_unless($postulacion->documento_pdf, 404);
        return Storage::disk('public')->download($postulacion->documento_pdf);
    }
}