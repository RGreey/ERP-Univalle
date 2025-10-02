<?php

namespace App\Http\Controllers;

use App\Models\ConvocatoriaSubsidio;
use App\Models\PostulacionSubsidio;
use App\Services\PrioridadNivelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AdminPostulacionSubsidioController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:AdminBienestar']);
    }

    public function index(ConvocatoriaSubsidio $convocatoria, Request $request)
    {
        $q      = $request->input('q');
        $estado = $request->input('estado');
        $sede   = $request->input('sede');

        $postulaciones = PostulacionSubsidio::with(['user','respuestas.pregunta'])
            ->where('convocatoria_id', $convocatoria->id)
            ->when($estado, fn($qb)=>$qb->where('estado',$estado))
            ->when($sede, fn($qb)=>$qb->where('sede',$sede))
            ->when($q, fn($qb)=>$qb->whereHas('user', fn($uq)=>$uq->where('name','like',"%$q%")->orWhere('email','like',"%$q%")))
            ->orderBy('estado')->orderBy('sede')->orderByDesc('created_at')
            ->paginate(15)->withQueryString();

        return view('roles.adminbienestar.convocatorias_subsidio.postulaciones.index', compact('convocatoria','postulaciones'));
    }

    public function show(PostulacionSubsidio $postulacion, PrioridadNivelService $prioridad)
    {
        $postulacion->load([
            'convocatoria.periodoAcademico',
            'user',
            'respuestas.pregunta.filas',
            'respuestas.pregunta.columnas',
            'respuestas.opcion',
        ]);

        $prio = $prioridad->calcular($postulacion);

        return view('roles.adminbienestar.convocatorias_subsidio.postulaciones.show', [
            'postulacion' => $postulacion,
            'prio'        => $prio,
        ]);
    }

    public function updateEstado(PostulacionSubsidio $postulacion, Request $request)
    {
        $data = $request->validate([
            'estado' => ['required','in:enviada,evaluada,beneficiario,rechazada,anulada'],
        ]);
        $postulacion->update(['estado' => $data['estado']]);
        return back()->with('success','Estado actualizado.');
    }

    public function download(PostulacionSubsidio $postulacion)
    {
        abort_unless($postulacion->documento_pdf, 404);
        return Storage::disk('public')->download($postulacion->documento_pdf);
    }

    public function recalcularPrioridad(PostulacionSubsidio $postulacion, PrioridadNivelService $prioridad)
    {
        $data = $prioridad->calcular($postulacion);

        $postulacion->update([
            'prioridad_base'         => $data['base'],
            'prioridad_final'        => $data['final'],
            'prioridad_calculada_en' => Carbon::now(),
        ]);

        return back()->with('success', 'Prioridad recalculada y guardada.');
    }

    // NUEVO: fijar prioridad manual
    public function updatePrioridadManual(PostulacionSubsidio $postulacion, Request $request)
    {
        $data = $request->validate([
            'prioridad_final' => ['required','integer','min:1','max:9'],
        ],[
            'prioridad_final.required' => 'Ingresa una prioridad.',
            'prioridad_final.integer'  => 'La prioridad debe ser un número.',
            'prioridad_final.min'      => 'La prioridad mínima es 1.',
            'prioridad_final.max'      => 'La prioridad máxima es 9.',
        ]);

        $postulacion->update([
            'prioridad_final'        => $data['prioridad_final'],
            'prioridad_calculada_en' => Carbon::now(),
        ]);

        return back()->with('success','Prioridad manual guardada.');
    }
}