<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;
use App\Models\DetallesEvento;
use App\Models\InventarioEvento;
use App\Models\ProgramaDependencia;
use App\Models\Lugar;
use App\Models\Espacio;
use App\Models\Anotacion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use App\Mail\EventoPDFMailable;
use Illuminate\Support\Facades\Mail;


class PDFController extends Controller
{


    public function generatePDF($id)
    {
        // Obtener el evento
        $evento = Evento::findOrFail($id);

        // Obtener el organizador (usuario) usando el campo 'user' del evento
        $organizador = User::find($evento->user);

        // Obtener los detalles del evento asociados
        $detallesEvento = DetallesEvento::where('evento', $id)->first();

        // Obtener el inventario del evento asociado
        $inventarioEvento = InventarioEvento::where('evento', $id)->get();

        // Obtener las dependencias asociadas al evento desde la tabla pivote
        $dependenciasIds = \DB::table('evento_dependencia')
            ->where('evento_id', $evento->id)
            ->pluck('programadependencia_id');

        // Obtener los nombres de las dependencias
        $programasDependencias = ProgramaDependencia::whereIn('id', $dependenciasIds)
            ->pluck('nombrePD')
            ->toArray();

        // Obtener el nombre del lugar y el espacio asociado
        $nombreLugar = Lugar::find($evento->lugar)?->nombreLugar;
        $nombreEspacio = Espacio::find($evento->espacio)?->nombreEspacio;

        // Obtener el historial de anotaciones
        $anotaciones = Anotacion::where('evento_id', $id)
            ->with('usuario:id,name')
            ->get()
            ->map(function ($anotacion) {
                $anotacion->archivo_url = $anotacion->archivo ? url('storages/' . $anotacion->archivo) : null;
                return $anotacion;
            });

        // Datos a enviar a la vista
        $informacionEvento = [
            'evento' => $evento,
            'detallesEvento' => $detallesEvento,
            'inventarioEvento' => $inventarioEvento,
            'programasDependencias' => $programasDependencias,
            'lugar' => $nombreLugar,
            'espacio' => $nombreEspacio,
            'anotaciones' => $anotaciones,
            'organizador' => $organizador?->name,
            'correoOrganizador' => $organizador?->email,
        ];

        // Generar el PDF
        $pdf = PDF::loadView('plantillapdf', compact('informacionEvento'));

        // Devolver el PDF al navegador
        return Response::make($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="evento.pdf"'
        ]);
    }




    public function enviarCorreo(Request $request)
    {
        $request->validate([
            'correos' => 'required|array',
            'asunto' => 'required|string',
            'contenido' => 'required|string',
            'evento_id' => 'required|exists:evento,id',
        ]);

        $evento = Evento::findOrFail($request->evento_id);

        // Generar el PDF
        $pdf = app(PDFController::class)->generatePDF($evento->id);

        // Extraer contenido del PDF como adjunto
        $pdfContent = $pdf->getContent();

        foreach ($request->correos as $correo) {
            Mail::to($correo)->send(new EventoPDFMailable(
                $request->asunto,
                $request->contenido,
                $pdfContent
            ));
        }

        return response()->json(['message' => 'Correos enviados correctamente']);
    }


}