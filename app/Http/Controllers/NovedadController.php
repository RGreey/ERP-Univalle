<?php

namespace App\Http\Controllers;

use App\Models\Novedad;
use App\Models\Evidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\MantenimientoRealizadoMail;

class NovedadController extends Controller
{
    // Listar novedades (puede filtrar por usuario, estado, etc.)
    public function index(Request $request)
    {
        $user = Auth::user();
        $novedades = Novedad::with(['lugar', 'usuario', 'evidencias'])
            ->when(!$this->esServiciosGenerales($user), fn($q) => $q->where('usuario_id', $user->id))
            ->when($request->estado, fn($q) => $q->where('estado_novedad', $request->estado))
            ->orderBy('fecha_solicitud', 'asc')
            ->get();
        
        // Si es una petición AJAX o desde la PWA, devolver JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($novedades);
        }
        
        return view('novedades.index', compact('novedades'));
    }

    /**
     * Verifica si el usuario pertenece a servicios generales por correo.
     */
    private function esServiciosGenerales($user)
    {
        $correosPermitidos = [
            'julio.ceballos@correounivalle.edu.co',
            'rubiel.gutierrez@correounivalle.edu.co',
            'jhonnathan.ososrio@correounivalle.edu.co',
            'rodrigo.buitrago@correounivalle.edu.co',
            'maria.cairasco@correounivalle.edu.co',
            'grajales.maria@correounivalle.edu.co',
            'diana.moscoso@correounivalle.edu.co',
            'luz.estella.quintero@correounivalle.edu.co',
            'loaiza.jhon@correounivalle.edu.co',
            'alarcon.ana@correounivalle.edu.co'
        ];
        return in_array($user->email, $correosPermitidos);
    }

    // Crear una nueva novedad
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'tipo' => 'required|string',
            'lugar_id' => 'required|exists:lugar,id',
            'ubicacion_detallada' => 'required|string',
        ]);

        $novedad = Novedad::create([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
            'lugar_id' => $request->lugar_id,
            'ubicacion_detallada' => $request->ubicacion_detallada,
            'usuario_id' => Auth::id(),
            'estado_novedad' => 'pendiente',
            'fecha_solicitud' => now(),
        ]);

        return redirect()->route('novedades.show', $novedad)->with('success', 'Novedad creada correctamente.');
    }

    // Ver detalle de una novedad
    public function show($id)
    {
        $novedad = Novedad::with(['lugar', 'usuario', 'evidencias'])->findOrFail($id);
        return view('novedades.show', compact('novedad'));
    }

    // Actualizar información o estado de la novedad
    public function update(Request $request, $id)
    {
        $novedad = Novedad::findOrFail($id);
        $request->validate([
            'estado_novedad' => 'nullable|string',
            'descripcion' => 'nullable|string',
        ]);
        if ($request->has('estado_novedad')) {
            $novedad->estado_novedad = $request->estado_novedad;
        }
        if ($request->has('descripcion')) {
            $novedad->descripcion = $request->descripcion;
        }
        if ($request->estado_novedad === 'finalizada') {
            $novedad->fecha_finalizacion = now();
        }
        $novedad->save();
        return redirect()->route('novedades.show', $novedad)->with('success', 'Novedad actualizada.');
    }

    // Agregar evidencia a una novedad
    public function addEvidencia(Request $request, $id)
    {
        $novedad = Novedad::findOrFail($id);
        $user = Auth::user();
        if (!$this->esServiciosGenerales($user)) {
            abort(403);
        }
        $request->validate([
            'archivo' => 'required',
            'archivo.*' => 'file|mimes:jpg,jpeg,png,pdf',
            'descripcion' => 'required|string|max:255',
        ]);
        if ($request->hasFile('archivo')) {
            foreach ($request->file('archivo') as $file) {
                $path = $file->store('evidencias', 'public');
                Evidencia::create([
                    'novedad_id' => $novedad->id,
                    'archivo_url' => $path,
                    'descripcion' => $request->descripcion,
                    'fecha_subida' => now(),
                ]);
            }
        }
        return redirect()->route('novedades.show', $novedad)->with('success', 'Evidencias agregadas.');
    }

    // Cerrar la novedad (por el solicitante)
    public function closeNovedad($id)
    {
        $novedad = Novedad::findOrFail($id);
        if (Auth::id() !== $novedad->usuario_id) {
            abort(403);
        }
        $novedad->estado_novedad = 'cerrada';
        $novedad->save();
        return redirect()->route('novedades.show', $novedad)->with('success', 'Novedad cerrada.');
    }

    /**
     * Cambiar el estado de la novedad a 'mantenimiento realizado' y notificar al solicitante.
     */
    public function updateEstado(Request $request, $id)
    {
        $novedad = Novedad::with('evidencias')->findOrFail($id);
        $user = Auth::user();
        if (!$this->esServiciosGenerales($user)) {
            abort(403);
        }
        $novedad->estado_novedad = 'mantenimiento realizado';
        $novedad->save();

        // Enviar correo al solicitante
        Mail::to($novedad->usuario->email)->send(new MantenimientoRealizadoMail($novedad));

        return redirect()->route('novedades.show', $novedad)->with('success', 'Estado cambiado a mantenimiento realizado y notificación enviada al solicitante.');
    }

    // (Opcional) Eliminar novedad
    public function destroy($id)
    {
        $novedad = Novedad::findOrFail($id);
        $novedad->delete();
        return redirect()->route('novedades.index')->with('success', 'Novedad eliminada.');
    }
} 