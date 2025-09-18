<?php

namespace App\Http\Controllers;

use App\Models\PaqueteEvidencia;
use App\Models\ActividadMantenimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PaqueteEvidenciaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'checkrole:Administrativo,Profesor,CooAdmin,AuxAdmin']);
    }

    public function index(Request $request)
    {
        $sedes = ['MI','VC','LI','Nodo'];
        $anios = range(now()->year - 2, now()->year + 1);
        $meses = range(1, 12);

        // Solo mostrar paquetes que tengan fotos (evidencias subidas)
        $query = PaqueteEvidencia::withCount('fotos')
            ->having('fotos_count', '>', 0) // Solo paquetes con fotos
            ->orderByDesc('anio')
            ->orderByDesc('mes');

        if ($request->filled('sede')) $query->where('sede', $request->sede);
        if ($request->filled('mes')) $query->where('mes', (int)$request->mes);
        if ($request->filled('anio')) $query->where('anio', (int)$request->anio);

        $paquetes = $query->paginate(15);

        return view('evidencias-mantenimiento.paquetes-index', compact('paquetes','sedes','meses','anios'));
    }

    public function generarPdf(PaqueteEvidencia $paquete)
    {
        $paquete->load(['fotos.actividad']);

        // Agrupar por actividad
        $porActividad = $paquete->fotos->groupBy('actividad_id')->map(function ($fotos, $actividadId) {
            $actividad = $fotos->first()->actividad;
            return [
                'actividad' => $actividad,
                'fotos' => $fotos,
            ];
        })->values();

        $pdf = Pdf::loadView('evidencias-mantenimiento.pdf.paquete', [
            'paquete' => $paquete,
            'porActividad' => $porActividad,
        ])->setPaper('letter');

        $fileName = "evidencias_{$paquete->sede}_{$paquete->anio}_" . str_pad($paquete->mes, 2, '0', STR_PAD_LEFT) . ".pdf";
        $path = 'evidencias/pdf/' . $fileName;
        Storage::disk('public')->put($path, $pdf->output());
        $paquete->update(['archivo_pdf' => $path]);

        return redirect()->back()->with('success', 'PDF generado correctamente.');
    }

    public function edit(PaqueteEvidencia $paquete)
    {
        return view('evidencias-mantenimiento.editar-paquete', compact('paquete'));
    }

    public function update(Request $request, PaqueteEvidencia $paquete)
    {
        $request->validate([
            'descripcion_general' => 'nullable|string|max:1000',
        ]);

        $paquete->update([
            'descripcion_general' => $request->descripcion_general,
        ]);

        return redirect()->route('evidencias-mantenimiento.index')
            ->with('success', 'Descripción general actualizada correctamente');
    }

    public function descargar(PaqueteEvidencia $paquete)
    {
        if (!$paquete->archivo_pdf || !Storage::disk('public')->exists($paquete->archivo_pdf)) {
            return redirect()->back()->with('error', 'PDF no generado aún.');
        }
        return Storage::disk('public')->download($paquete->archivo_pdf);
    }

    public function previsualizar(PaqueteEvidencia $paquete)
    {
        // Siempre generar el PDF para tener los estilos más actualizados
        $paquete->load(['fotos.actividad']);

        // Agrupar por actividad
        $porActividad = $paquete->fotos->groupBy('actividad_id')->map(function ($fotos, $actividadId) {
            $actividad = $fotos->first()->actividad;
            return [
                'actividad' => $actividad,
                'fotos' => $fotos,
            ];
        })->values();

        $pdf = Pdf::loadView('evidencias-mantenimiento.pdf.paquete', [
            'paquete' => $paquete,
            'porActividad' => $porActividad,
        ])->setPaper('letter');

        $fileName = "evidencias_{$paquete->sede}_{$paquete->anio}_" . str_pad($paquete->mes, 2, '0', STR_PAD_LEFT) . ".pdf";
        $path = 'evidencias/pdf/' . $fileName;
        Storage::disk('public')->put($path, $pdf->output());
        $paquete->update(['archivo_pdf' => $path]);
        
        $path = Storage::disk('public')->path($paquete->archivo_pdf);
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($paquete->archivo_pdf) . '"'
        ]);
    }

    /**
     * Eliminar todas las fotos y PDF de un paquete para empezar de cero
     */
    public function eliminarPdf(PaqueteEvidencia $paquete)
    {
        try {
            // Eliminar todas las fotos del paquete
            $fotosEliminadas = 0;
            $archivosEliminados = 0;
            
            foreach ($paquete->fotos as $foto) {
                // Eliminar archivo físico de la foto
                if (Storage::disk('public')->exists($foto->archivo)) {
                    Storage::disk('public')->delete($foto->archivo);
                    $archivosEliminados++;
                }
                
                // Eliminar registro de la foto
                $foto->delete();
                $fotosEliminadas++;
            }
            
            // Eliminar PDF si existe
            if ($paquete->archivo_pdf && Storage::disk('public')->exists($paquete->archivo_pdf)) {
                Storage::disk('public')->delete($paquete->archivo_pdf);
                $archivosEliminados++;
            }
            
            // Limpiar referencia del PDF en la base de datos
            $paquete->update(['archivo_pdf' => null]);
            
            return redirect()->back()->with('success', "Paquete limpiado correctamente. Se eliminaron {$fotosEliminadas} fotos y {$archivosEliminados} archivos. Ahora puedes subir nuevas evidencias desde el PWA.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al limpiar el paquete: ' . $e->getMessage());
        }
    }

    /**
     * Limpiar archivos PDF huérfanos
     */
    public function limpiarArchivos()
    {
        try {
            // Ejecutar el comando
            \Artisan::call('evidencias:limpiar-archivos', ['--confirmar' => true]);
            
            $output = \Artisan::output();
            
            return redirect()->back()->with('success', 'Archivos PDF huérfanos eliminados correctamente.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al limpiar archivos: ' . $e->getMessage());
        }
    }
}


