<?php

namespace App\Http\Controllers;

use App\Models\ConvocatoriaSubsidio;
use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ConvocatoriaSubsidioController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'checkrole:AdminBienestar']);
    }

    public function index(Request $request)
    {
        $q        = $request->input('q');
        $estado   = $request->input('estado');   // borrador | activa | cerrada
        $periodo  = $request->input('periodo');
        $vigencia = $request->input('vigencia'); // abiertas | cerradas | proximas

        $hoy = Carbon::today();

        $query = ConvocatoriaSubsidio::with('periodoAcademico')
            ->when($q, fn($qb) => $qb->where('nombre', 'like', '%'.$q.'%'))
            // Estado calculado por fechas (scope)
            ->estadoActual($estado)
            // Periodo
            ->when($periodo, fn($qb) => $qb->where('periodo_academico', $periodo))
            // Filtros de vigencia por fechas
            ->when($vigencia === 'abiertas', function ($qb) use ($hoy) {
                $qb->whereDate('fecha_apertura', '<=', $hoy)
                   ->whereDate('fecha_cierre', '>=', $hoy);
            })
            ->when($vigencia === 'cerradas', function ($qb) use ($hoy) {
                $qb->whereDate('fecha_cierre', '<', $hoy);
            })
            ->when($vigencia === 'proximas', function ($qb) use ($hoy) {
                $qb->whereBetween('fecha_apertura', [$hoy, $hoy->copy()->addDays(30)]);
            });

        $convocatorias = $query->orderByDesc('fecha_apertura')
            ->paginate(12)
            ->withQueryString();

        $periodos = PeriodoAcademico::orderBy('fechaInicio', 'desc')->get();

        return view('roles.adminbienestar.convocatorias_subsidio.index', compact('convocatorias', 'periodos'));
    }

    public function create()
    {
        $periodos = PeriodoAcademico::orderBy('fechaInicio', 'desc')->get();
        return view('roles.adminbienestar.convocatorias_subsidio.create', compact('periodos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'periodo_academico' => ['required', 'exists:periodoAcademico,id'],
            'fecha_apertura' => ['required', 'date'],
            'fecha_cierre' => ['required', 'date', 'after_or_equal:fecha_apertura'],
            'cupos_caicedonia' => ['required', 'integer', 'min:0'],
            'cupos_sevilla' => ['required', 'integer', 'min:0'],
            // estado ya no viene del request
        ]);

        ConvocatoriaSubsidio::create($data); // el modelo calculará estado

        return redirect()->route('admin.convocatorias-subsidio.index')
            ->with('success', 'Convocatoria creada correctamente.');
    }

    public function edit($id)
    {
        $convocatoria = ConvocatoriaSubsidio::findOrFail($id);
        $periodos = PeriodoAcademico::orderBy('fechaInicio', 'desc')->get();
        return view('roles.adminbienestar.convocatorias_subsidio.edit', compact('convocatoria', 'periodos'));
    }

    public function update(Request $request, $id)
    {
        $convocatoria = ConvocatoriaSubsidio::findOrFail($id);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'periodo_academico' => ['required', 'exists:periodoAcademico,id'],
            'fecha_apertura' => ['required', 'date'],
            'fecha_cierre' => ['required', 'date', 'after_or_equal:fecha_apertura'],
            'cupos_caicedonia' => ['required', 'integer', 'min:0'],
            'cupos_sevilla' => ['required', 'integer', 'min:0'],
        ]);

        $convocatoria->update($data); // el modelo recalculará estado

        return redirect()->route('admin.convocatorias-subsidio.index')
            ->with('success', 'Convocatoria actualizada correctamente.');
    }

    public function destroy($id)
    {
        $convocatoria = ConvocatoriaSubsidio::findOrFail($id);
        $convocatoria->delete();

        return redirect()->route('admin.convocatorias-subsidio.index')
            ->with('success', 'Convocatoria eliminada correctamente.');
    }
}