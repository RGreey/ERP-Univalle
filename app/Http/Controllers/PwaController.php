<?php

namespace App\Http\Controllers;

use App\Models\Novedad;
use App\Models\Evidencia;
use App\Mail\MantenimientoRealizadoMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PwaController extends Controller
{
    /**
     * Mostrar la vista del PWA
     */
    public function index()
    {
        return view('pwa.index');
    }

    /**
     * Obtener novedades pendientes
     */
    public function getNovedades()
    {
        $novedades = Novedad::with(['lugar', 'usuario', 'evidencias'])
            ->where('estado_novedad', 'pendiente')
            ->orderBy('fecha_solicitud', 'asc')
            ->get();
        
        return response()->json($novedades);
    }

    /**
     * Subir evidencia para una novedad
     */
    public function uploadEvidencia(Request $request, $id)
    {
        $novedad = Novedad::findOrFail($id);
        
        $request->validate([
            'archivo' => 'required|file|image|max:10240', // 10MB max
            'descripcion' => 'nullable|string|max:255',
        ]);
        
        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('evidencias', $filename, 'public');
            
            Evidencia::create([
                'novedad_id' => $novedad->id,
                'archivo_url' => $path,
                'descripcion' => $request->descripcion,
                'fecha_subida' => now(),
            ]);
            
            return response()->json(['success' => true, 'message' => 'Evidencia subida correctamente']);
        }
        
        return response()->json(['success' => false, 'message' => 'No se pudo subir la evidencia'], 400);
    }

    /**
     * Marcar mantenimiento como realizado
     */
    public function marcarMantenimientoRealizado($id)
    {
        try {
            $novedad = Novedad::with(['usuario', 'evidencias'])->findOrFail($id);
            
            // Verificar que tenga al menos una evidencia
            if ($novedad->evidencias->count() == 0) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Debe subir al menos una evidencia antes de marcar como mantenimiento realizado'
                ], 400);
            }
            
            // Verificar que el usuario tenga email
            if (!$novedad->usuario || !$novedad->usuario->email) {
                return response()->json([
                    'success' => false, 
                    'message' => 'El usuario no tiene un email válido'
                ], 400);
            }
            
            // Cambiar estado
            $novedad->estado_novedad = 'mantenimiento realizado';
            $novedad->save();
            
            // Enviar email de notificación
            try {
                Mail::to($novedad->usuario->email)->send(new MantenimientoRealizadoMail($novedad));
                Log::info('Email de mantenimiento enviado correctamente a: ' . $novedad->usuario->email);
            } catch (\Exception $e) {
                Log::error('Error enviando email de mantenimiento: ' . $e->getMessage());
                // Si falla el email, no es crítico, pero lo registramos
            }
            
            return response()->json(['success' => true, 'message' => 'Estado cambiado a mantenimiento realizado']);
            
        } catch (\Exception $e) {
            Log::error('Error en marcarMantenimientoRealizado: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false, 
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }
} 