<?php

namespace App\Http\Controllers;

use App\Models\ConvocatoriaSubsidio;
use App\Models\PeriodoAcademico;
use App\Models\EncuestaSubsidio;
use Illuminate\Http\Request;

class ConvocatoriaSubsidioController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'checkrole:AdminBienestar']);
    }

    public function index(Request $request)
    {
        $q        = $request->input('q');
        $estado   = $request->input('estado');
        $periodo  = $request->input('periodo');
        $vigencia = $request->input('vigencia');

        $query = ConvocatoriaSubsidio::with(['periodoAcademico'])->withCount('postulaciones')
            ->when($q, fn($qb)=>$qb->where('nombre','like',"%{$q}%"))
            ->when($estado, fn($qb)=>$qb->where('estado',$estado))
            ->when($periodo, fn($qb)=>$qb->where('periodo_academico',$periodo));

        $convocatorias = $query->orderByDesc('created_at')->paginate(12)->withQueryString();
        $periodos = PeriodoAcademico::orderBy('fechaInicio','desc')->get();

        return view('roles.adminbienestar.convocatorias_subsidio.index', compact('convocatorias','periodos'));
    }

    public function create()
    {
        $periodos   = PeriodoAcademico::orderBy('fechaInicio','desc')->get();
        $encuestas  = EncuestaSubsidio::orderBy('nombre')->get(['id','nombre']);
        return view('roles.adminbienestar.convocatorias_subsidio.create', compact('periodos','encuestas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'                  => ['required','string','max:255'],
            'periodo_academico'       => ['required','exists:periodoAcademico,id'],
            'fecha_apertura'          => ['required','date'],
            'fecha_cierre'            => ['required','date','after_or_equal:fecha_apertura'],

            'fecha_inicio_beneficio'  => ['nullable','date','after_or_equal:fecha_apertura'],
            'fecha_fin_beneficio'     => ['nullable','date','after_or_equal:fecha_inicio_beneficio'],

            'cupos_caicedonia'        => ['required','integer','min:0'],
            'cupos_sevilla'           => ['required','integer','min:0'],

            'encuesta_id'             => ['nullable','exists:subsidio_encuestas,id'],
            'recepcion_habilitada'    => ['sometimes','boolean'],
        ]);

        $this->normalizeNullableDates($data, ['fecha_inicio_beneficio','fecha_fin_beneficio']);
        $data['recepcion_habilitada'] = $request->boolean('recepcion_habilitada', true);

        ConvocatoriaSubsidio::create($data);

        return redirect()->route('admin.convocatorias-subsidio.index')
            ->with('success','Convocatoria creada correctamente.');
    }

    public function edit($id)
    {
        $convocatoria = ConvocatoriaSubsidio::findOrFail($id);
        $periodos     = PeriodoAcademico::orderBy('fechaInicio','desc')->get();
        $encuestas    = EncuestaSubsidio::orderBy('nombre')->get(['id','nombre']);
        return view('roles.adminbienestar.convocatorias_subsidio.edit', compact('convocatoria','periodos','encuestas'));
    }

    public function update(Request $request, $id)
    {
        $convocatoria = ConvocatoriaSubsidio::findOrFail($id);

        $data = $request->validate([
            'nombre'                  => ['required','string','max:255'],
            'periodo_academico'       => ['required','exists:periodoAcademico,id'],
            'fecha_apertura'          => ['required','date'],
            'fecha_cierre'            => ['required','date','after_or_equal:fecha_apertura'],

            'fecha_inicio_beneficio'  => ['nullable','date','after_or_equal:fecha_apertura'],
            'fecha_fin_beneficio'     => ['nullable','date','after_or_equal:fecha_inicio_beneficio'],

            'cupos_caicedonia'        => ['required','integer','min:0'],
            'cupos_sevilla'           => ['required','integer','min:0'],

            'encuesta_id'             => ['nullable','exists:subsidio_encuestas,id'],
            'recepcion_habilitada'    => ['sometimes','boolean'],
        ]);

        $this->normalizeNullableDates($data, ['fecha_inicio_beneficio','fecha_fin_beneficio']);
        $data['recepcion_habilitada'] = $request->boolean('recepcion_habilitada', true);

        $convocatoria->update($data);

        return redirect()->route('admin.convocatorias-subsidio.index')
            ->with('success','Convocatoria actualizada correctamente.');
    }

    public function destroy($id)
    {
        $convocatoria = ConvocatoriaSubsidio::findOrFail($id);
        $convocatoria->delete();

        return redirect()->route('admin.convocatorias-subsidio.index')
            ->with('success','Convocatoria eliminada correctamente.');
    }

    private function normalizeNullableDates(array &$data, array $keys): void
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $data) && $data[$k] === '') {
                $data[$k] = null;
            }
        }
    }
}