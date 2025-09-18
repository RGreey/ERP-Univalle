<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PeriodoAcademico;

class PeriodoAcademicoController extends Controller
{
    public function index()
    {
        $periodos = PeriodoAcademico::orderBy('fechaInicio', 'desc')->get();
        return response()->json($periodos);
    }

    public function create()
    {
        $periodos = PeriodoAcademico::orderBy('fechaInicio', 'desc')->get();
        return view('monitoria.crearPeriodoA', compact('periodos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after:fechaInicio',
            'tipoPeriodo' => 'required|in:regular,intersemestral'
        ]);

        $year = date('Y', strtotime($request->fechaInicio));
        $month = date('m', strtotime($request->fechaInicio));
        
        // Generar el nombre del período según el tipo
        if ($request->tipoPeriodo === 'intersemestral') {
            $baseNombre = "$year-Intersemestral";
            // Buscar cuántos intersemestrales existen ya en ese año
            $count = PeriodoAcademico::where('nombre', 'like', "$baseNombre%")
                ->whereYear('fechaInicio', $year)
                ->count();
            $romanos = ['I', 'II', 'III', 'IV', 'V'];
            $indice = $count < count($romanos) ? $romanos[$count] : ($count + 1);
            $nombre = "$baseNombre-$indice";
        } else {
            $nombre = $month <= 6 ? "$year-I" : "$year-II";
        }

        // Verificar si ya existe un período académico con el mismo nombre
        $existingPeriodo = PeriodoAcademico::where('nombre', $nombre)->first();
        if ($existingPeriodo) {
            return response()->json(['error' => 'Ya existe un período académico con este nombre.'], 409);
        }

        // Verificar si hay solapamiento de fechas con otros períodos
        $solapamiento = PeriodoAcademico::where(function($query) use ($request) {
            $query->whereBetween('fechaInicio', [$request->fechaInicio, $request->fechaFin])
                  ->orWhereBetween('fechaFin', [$request->fechaInicio, $request->fechaFin])
                  ->orWhere(function($q) use ($request) {
                      $q->where('fechaInicio', '<=', $request->fechaInicio)
                        ->where('fechaFin', '>=', $request->fechaFin);
                  });
        })->exists();

        if ($solapamiento) {
            return response()->json(['error' => 'Existe un solapamiento de fechas con otro período académico.'], 409);
        }

        try {
            $periodo = PeriodoAcademico::create([
                'nombre' => $nombre,
                'tipo' => $request->tipoPeriodo,
                'fechaInicio' => $request->fechaInicio,
                'fechaFin' => $request->fechaFin,
            ]);

            if ($periodo) {
                return response()->json($periodo, 201);
            } else {
                return response()->json(['error' => 'Hubo un problema al crear el período académico.'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Hubo un problema al crear el período académico.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $periodo = PeriodoAcademico::findOrFail($id);
        
        $request->validate([
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after:fechaInicio',
            'tipoPeriodo' => 'required|in:regular,intersemestral'
        ]);

        // Generar el nuevo nombre del período según el tipo
        $year = date('Y', strtotime($request->fechaInicio));
        $month = date('m', strtotime($request->fechaInicio));
        
        if ($request->tipoPeriodo === 'intersemestral') {
            $baseNombre = "$year-Intersemestral";
            // Buscar cuántos intersemestrales existen ya en ese año, excluyendo el actual
            $count = PeriodoAcademico::where('nombre', 'like', "$baseNombre%")
                ->whereYear('fechaInicio', $year)
                ->where('id', '!=', $id)
                ->count();
            $romanos = ['I', 'II', 'III', 'IV', 'V'];
            $indice = $count < count($romanos) ? $romanos[$count] : ($count + 1);
            $nombre = "$baseNombre-$indice";
        } else {
            $nombre = $month <= 6 ? "$year-I" : "$year-II";
        }

        // Verificar si ya existe un período académico con el mismo nombre (excluyendo el actual)
        $existingPeriodo = PeriodoAcademico::where('nombre', $nombre)
            ->where('id', '!=', $id)
            ->first();
        if ($existingPeriodo) {
            return response()->json(['error' => 'Ya existe un período académico con este nombre.'], 409);
        }

        // Verificar si hay solapamiento de fechas con otros períodos (excluyendo el actual)
        $solapamiento = PeriodoAcademico::where('id', '!=', $id)
            ->where(function($query) use ($request) {
                $query->whereBetween('fechaInicio', [$request->fechaInicio, $request->fechaFin])
                      ->orWhereBetween('fechaFin', [$request->fechaInicio, $request->fechaFin])
                      ->orWhere(function($q) use ($request) {
                          $q->where('fechaInicio', '<=', $request->fechaInicio)
                            ->where('fechaFin', '>=', $request->fechaFin);
                      });
            })->exists();

        if ($solapamiento) {
            return response()->json(['error' => 'Existe un solapamiento de fechas con otro período académico.'], 409);
        }

        try {
            $periodo->update([
                'nombre' => $nombre,
                'tipo' => $request->tipoPeriodo,
                'fechaInicio' => $request->fechaInicio,
                'fechaFin' => $request->fechaFin
            ]);

            return response()->json($periodo);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el período académico.'], 500);
        }
    }

    public function destroy($id)
    {
        $periodo = PeriodoAcademico::findOrFail($id);
        
        // Verificar si el período está siendo usado en convocatorias
        if ($periodo->convocatorias()->exists()) {
            return response()->json(['error' => 'No se puede eliminar el período porque está asociado a convocatorias.'], 409);
        }

        $periodo->delete();
        return response()->json(['message' => 'Período académico eliminado correctamente']);
    }
}
