<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Postulado;
use App\Models\Monitor;
use App\Models\Convocatoria;
use App\Models\Documento; 
use App\Models\User; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\PostuladoCorreo;
use App\Mail\EntrevistaProgramadaMail;
use App\Models\Monitoria;
use App\Helpers\ConvocatoriaHelper;

class PostuladoController extends Controller
{
    public function index()
    {
        // Buscar convocatoria activa usando el helper
        $convocatoriaActiva = ConvocatoriaHelper::obtenerConvocatoriaActiva();
        
        if (!$convocatoriaActiva) {
            return redirect()->route('convocatoria.index')->with('error', 'No hay convocatoria activa o en período de entrevistas.');
        }
        
        // Consulta los postulados y une con la tabla de usuarios
        $postulados = Postulado::where('postulados.convocatoria', $convocatoriaActiva->id)
        ->join('users', 'postulados.user', '=', 'users.id')
        ->join('monitorias', 'postulados.monitoria', '=', 'monitorias.id')
        ->select('postulados.*', 'users.name as name', 'users.email as email', 'monitorias.nombre as monitoria_nombre')
        ->with(['documentos', 'monitor']) // Cargar la relación monitor
        ->get();
        
        // Determinar el estado del período de convocatoria usando el helper
        $estadoPeriodo = 'abierta'; // Por defecto
        
        if (ConvocatoriaHelper::convocatoriaEnEntrevistas($convocatoriaActiva->fechaCierre, $convocatoriaActiva->fechaEntrevistas)) {
            $estadoPeriodo = 'entrevistas';
        } elseif (now()->gt($convocatoriaActiva->fechaEntrevistas)) {
            $estadoPeriodo = 'finalizada';
        }

        return view('monitoria.listaPostulados', compact('postulados', 'convocatoriaActiva', 'estadoPeriodo'));
    }

    public function update(Request $request, $id)
    {
        try {
        $postulado = Postulado::findOrFail($id);
        $nuevoEstado = $request->input('estado');
            $estadoAnterior = $postulado->estado;
        
            // Si el estado no ha cambiado, no hacer nada
            if ($estadoAnterior === $nuevoEstado) {
                return redirect()->back()->with('info', 'El estado no ha cambiado.');
            }

            // Verificar restricciones por período de tiempo usando el helper
            $convocatoria = Convocatoria::find($postulado->convocatoria);
            $enPeriodoEntrevistas = ConvocatoriaHelper::convocatoriaEnEntrevistas($convocatoria->fechaCierre, $convocatoria->fechaEntrevistas);

            // Restringir aprobaciones para entrevista durante el período de entrevistas
            if ($enPeriodoEntrevistas && $nuevoEstado === 'aprobado_entrevista') {
                return redirect()->back()->with('error', 
                    'Ya no se pueden aprobar más postulados para entrevista. El período de selección para entrevistas ha finalizado. Solo se pueden gestionar las entrevistas ya programadas.'
                );
            }

            // Restringir que estudiantes ya aprobados para entrevista vuelvan a pendiente durante período de entrevistas
            if ($enPeriodoEntrevistas && $estadoAnterior === 'aprobado_entrevista' && $nuevoEstado === 'pendiente') {
                return redirect()->back()->with('error', 
                    'No se puede cambiar a pendiente un postulado que ya fue aprobado para entrevista durante el período de entrevistas. Solo se puede rechazar si no cumple con los requisitos tras la evaluación.'
                );
            }
    
            // Verificar si el nuevo estado es aprobado
        if ($nuevoEstado === 'aprobado') {
                // Verificar si ya se alcanzó el número máximo de monitores para esta monitoría
                $monitoria = Monitoria::find($postulado->monitoria);
                $monitoresAprobados = Monitor::where('monitoria', $postulado->monitoria)
                    ->where('estado', 'activo')
                    ->count();
                    
                if ($monitoresAprobados >= $monitoria->vacante) {
                    return redirect()->back()->with('error', 
                        'Ya se alcanzó el número máximo de monitores para esta monitoría (' . $monitoria->vacante . ' vacantes).'
                    );
                }

                // Verificar si el usuario ya es monitor activo en otra monitoría
                $monitorEnOtraMonitoria = Monitor::where('user', $postulado->user)
                ->where('monitoria', '!=', $postulado->monitoria)
                ->where('estado', 'activo')
                ->first();
    
                if ($monitorEnOtraMonitoria) {
                    return redirect()->back()->with('error', 
                        'Este estudiante ya es monitor activo en otra monitoría.'
                    );
            }

                // Crear el registro en la tabla monitor
                Monitor::create([
                    'user' => $postulado->user,
                    'monitoria' => $postulado->monitoria,
                    'fecha_vinculacion' => null,
                    'fecha_culminacion' => null
                ]);
                // Redirigir con aviso especial
                $postulado->estado = $nuevoEstado;
                $postulado->save();
                return redirect()->back()->with('success', 'Estado actualizado correctamente.')->with('aviso_aprobado', true);
            } elseif ($estadoAnterior === 'aprobado') {
                // Si el estado cambia de aprobado a otro, eliminar el registro de la tabla monitor
                Monitor::where('user', $postulado->user)
                    ->where('monitoria', $postulado->monitoria)
                    ->delete();
        }
    
            // Actualizar el estado del postulado
        $postulado->estado = $nuevoEstado;
        $postulado->save();
    
        return redirect()->back()->with('success', 'Estado actualizado correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error al actualizar postulado: ' . $e->getMessage());
            return redirect()->back()->with('error', 
                'Ocurrió un error al actualizar el estado. Por favor, intenta nuevamente.'
            );
        }
    }
    
    public function storeFechas(Request $request, $postuladoId)
    {
        try {
        $validated = $request->validate([
                'fecha_vinculacion' => 'nullable|date',
                'fecha_culminacion' => 'nullable|date|after:fecha_vinculacion',
        ]);
    
        // Obtener el postulado
        $postulado = Postulado::findOrFail($postuladoId);
        
            // Verificar que el postulado esté aprobado
            if ($postulado->estado !== 'aprobado') {
                return redirect()->back()->with('error', 
                    'No se pueden agregar fechas a un postulado que no está aprobado.'
                );
            }

            // Verificar que exista el registro en la tabla monitor
            $monitor = Monitor::where('user', $postulado->user)
                ->where('monitoria', $postulado->monitoria)
                ->first();

            if (!$monitor) {
                return redirect()->back()->with('error', 
                    'No se encontró el registro del monitor. Por favor, verifica el estado del postulado.'
                );
            }
            
            // Actualizar el registro en la tabla monitor
            $monitor->update([
                'fecha_vinculacion' => $validated['fecha_vinculacion'] ?? null,
                'fecha_culminacion' => $validated['fecha_culminacion'] ?? null
        ]);
    
        // Actualizar el estado del postulado a "Aprobado"
        $postulado->estado = 'aprobado';
        $postulado->save();
    
            return redirect()->route('postulados.index')->with('success', 
                'Fechas de vinculación actualizadas correctamente.'
            );
        } catch (\Exception $e) {
            \Log::error('Error al actualizar fechas: ' . $e->getMessage());
            return redirect()->back()->with('error', 
                'Ocurrió un error al actualizar las fechas. Por favor, intenta nuevamente.'
            );
        }
    }
    
    
    
    public function store(Request $request)
    {
        // Validar que el archivo ha sido subido
        $request->validate([
            'documento_url' => 'required|file|mimes:pdf',
            'monitoria_id' => 'required|exists:monitorias,id',
            'cedula' => 'required|string|min:5',
        ]);

        // Obtener la convocatoria activa usando el helper
        $convocatoria_activa = ConvocatoriaHelper::obtenerConvocatoriaActiva();

        if (!$convocatoria_activa) {
            return redirect()->back()->with('error', 'No hay convocatorias activas en este momento.');
        }

        // Verificar si la convocatoria está en período de entrevistas usando el helper
        if (ConvocatoriaHelper::convocatoriaEnEntrevistas($convocatoria_activa->fechaCierre, $convocatoria_activa->fechaEntrevistas)) {
            return redirect()->back()->with('error', 'El período de postulaciones ha finalizado. La convocatoria está en período de entrevistas.');
        }

        // Obtener el ID del usuario autenticado
        $usuario_id = Auth::id();
        
        // Actualizar cédula del usuario si viene en la postulación
        $cedula = $request->input('cedula');
        if ($cedula) {
            $user = User::find($usuario_id);
            if ($user && (!$user->cedula || $user->cedula !== $cedula)) {
                $user->cedula = $cedula;
                $user->save();
            }
        }

        // Verificar si ya existe una postulación para esta monitoria
        $existingPostulado = Postulado::where('user', $usuario_id)
                                    ->where('monitoria', $request->monitoria_id)
                                    ->first();

        if ($existingPostulado) {
            return redirect()->back()->with('error', 'Ya has postulado a esta monitoria.');
        }

        // Crear una nueva postulación
        $postulado = Postulado::create([
            'user' => $usuario_id,
            'convocatoria' => $convocatoria_activa->id,
            'monitoria' => $request->monitoria_id,
            'estado' => 'pendiente', 
        ]);

        // Guardar el documento
        $documento = $request->file('documento_url');
        $fecha = Carbon::now()->format('Y-m-d');
        $tiempo = time();
        $extension = $documento->getClientOriginalExtension();
        $nombreDocumento = "{$fecha}-documentos-{$usuario_id}-{$tiempo}.{$extension}";
        $rutaDocumento = $documento->storeAs('documentos', $nombreDocumento, 'public');

        // Crear el registro del documento en la base de datos
        Documento::create([
            'nombreDocumento' => $nombreDocumento,
            'url' => $rutaDocumento,
            'postulado' => $postulado->id, 
        ]);

        return redirect()->back()->with('success', 'Te has postulado correctamente a la monitoria.');
    }


    public function destroy($monitoriaId)
    {
        $userId = auth()->user()->id;
        
        // Obtener la postulación del usuario logeado para la monitoria seleccionada
        $postulacion = Postulado::where('monitoria', $monitoriaId)
                                ->where('user', $userId)
                                ->first();

        if ($postulacion) {
            // Obtener el documento asociado
            $documento = Documento::where('postulado', $postulacion->id)->first();

            // Eliminar el documento si existe
            if ($documento) {
                // Eliminar el archivo del almacenamiento
                if (Storage::disk('public')->exists($documento->url)) {
                    Storage::disk('public')->delete($documento->url);
                }
                $documento->delete(); // Eliminar registro del documento
            }

            // Eliminar la postulación
            $postulacion->delete();

            return response()->json(['success' => 'Postulación y documento eliminados correctamente.']);
        } else {
            return response()->json(['error' => 'No se encontró la postulación.'], 404);
        }
    }

    public function getDocument($monitoriaId)
    {
        // Obtener el usuario autenticado
        $usuario_id = Auth::id();

        // Obtener la postulación para la monitoria del usuario actual
        $postulado = Postulado::where('monitoria', $monitoriaId)
                            ->where('user', $usuario_id)
                            ->first();

        if (!$postulado) {
            return response()->json(['message' => 'Postulación no encontrada.']);
        }

        // Obtener el documento asociado a la postulación
        $documento = Documento::where('postulado', $postulado->id)->first();

        if (!$documento) {
            return response()->json(['message' => 'Documento no encontrado.'], 404);
        }

        // Generar la URL pública del documento usando la configuración actual
        $documentoUrl = Storage::url($documento->url);

        return response()->json([
            'documento' => [
                'url' => $documentoUrl,
                'nombreDocumento' => $documento->nombreDocumento
            ]
        ]);
    }

    public function enviarCorreo(Request $request, $id)
    {
        $postulado = Postulado::find($id);
    
        if (!$postulado) {
            return redirect()->back()->withErrors(['error' => 'Postulado no encontrado.']);
        }
    
        $user = User::find($postulado->user);
    
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'Usuario no encontrado.']);
        }
    
        $path = null;
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('imagenes', 'public');
        }
    
        $detalles = $request->input('detalles');
        $instrucciones = $request->input('instrucciones');
        $monitoria_nombre = \App\Models\Monitoria::find($postulado->monitoria)->nombre ?? 'No disponible';
        
        Mail::to($user->email)->send(new PostuladoCorreo($postulado, $detalles, $path ? $path : null, $user, $instrucciones, $monitoria_nombre));
    
        return redirect()->route('postulados.index')->with('correo_enviado', 'El correo ha sido enviado exitosamente.');
    }
        /**
     * Vista para el encargado: gestionar entrevistas de postulados a su monitoría
     */
    public function entrevistas(Request $request)
    {
        $user = auth()->user();
        // Buscar convocatoria activa usando el helper
        $convocatoriaActiva = ConvocatoriaHelper::obtenerConvocatoriaActiva();
        
        if (!$convocatoriaActiva) {
            return redirect()->route('convocatoria.index')->with('error', 'No hay convocatoria activa o en período de entrevistas.');
        }
        $monitorias = \App\Models\Monitoria::where('encargado', $user->id)->pluck('id');
        // Traer postulados de la convocatoria activa, de la monitoría, con estado aprobado_entrevista o aprobado
        $postulados = \App\Models\Postulado::where('postulados.convocatoria', $convocatoriaActiva->id)
            ->whereIn('postulados.monitoria', $monitorias)
            ->join('users', 'postulados.user', '=', 'users.id')
            ->join('monitorias', 'postulados.monitoria', '=', 'monitorias.id')
            ->select('postulados.*', 'users.name as user_name', 'users.email as user_email', 'monitorias.nombre as monitoria_nombre')
            ->with(['documentos'])
            ->whereIn('postulados.estado', ['aprobado_entrevista', 'aprobado'])
            ->get();
        $nombreMonitoria = null;
        if ($monitorias->count() === 1) {
            $nombreMonitoria = \App\Models\Monitoria::find($monitorias->first())->nombre ?? null;
        }
        
        // Determinar el estado del período de convocatoria usando el helper
        $estadoPeriodo = 'abierta'; // Por defecto
        
        if (ConvocatoriaHelper::convocatoriaEnEntrevistas($convocatoriaActiva->fechaCierre, $convocatoriaActiva->fechaEntrevistas)) {
            $estadoPeriodo = 'entrevistas';
        } elseif (now()->gt($convocatoriaActiva->fechaEntrevistas)) {
            $estadoPeriodo = 'finalizada';
        }
        
        return view('monitoria.entrevistasPostulados', compact('postulados', 'nombreMonitoria', 'convocatoriaActiva', 'estadoPeriodo'));
    }
    /**
     * Actualizar datos de entrevista y estado del postulado (solo encargado)
     */
    
    public function guardarEntrevista(Request $request, $id)
    {
        // Log para debugging
        Log::info('Iniciando guardarEntrevista', [
            'id' => $id,
            'request_data' => $request->all(),
            'user_id' => auth()->id()
        ]);
        
        try {
            $request->validate([
                'entrevista_fecha' => 'required|date',
                'entrevista_medio' => 'required|in:presencial,virtual',
                'entrevista_link' => 'nullable|string',
                'entrevista_lugar' => 'nullable|string',
                'concepto_entrevista' => 'required|string',
            ]);
            
            $postulado = \App\Models\Postulado::findOrFail($id);
            $monitoria = \App\Models\Monitoria::find($postulado->monitoria);
            
            Log::info('Datos encontrados', [
                'postulado_id' => $postulado->id,
                'monitoria_id' => $monitoria ? $monitoria->id : null,
                'monitoria_encargado' => $monitoria ? $monitoria->encargado : null,
                'auth_id' => auth()->id()
            ]);
            
            if (!$monitoria || $monitoria->encargado != auth()->id()) {
                Log::error('No autorizado para guardar entrevista', [
                    'monitoria_encargado' => $monitoria ? $monitoria->encargado : null,
                    'auth_id' => auth()->id()
                ]);
                abort(403, 'No autorizado');
            }
            
            // Guardar los datos de la entrevista
        $postulado->entrevista_fecha = $request->entrevista_fecha;
        $postulado->entrevista_medio = $request->entrevista_medio;
        $postulado->entrevista_link = $request->entrevista_medio === 'virtual' ? $request->entrevista_link : null;
        $postulado->entrevista_lugar = $request->entrevista_medio === 'presencial' ? $request->entrevista_lugar : null;
        $postulado->concepto_entrevista = $request->concepto_entrevista;
        $postulado->entrevistador = auth()->user()->name;
        $postulado->save();
        
        // Enviar correo al postulado
        try {
            $user = User::find($postulado->user);
            if ($user) {
                Mail::to($user->email)->send(new EntrevistaProgramadaMail($postulado, $user, $monitoria, auth()->user()));
            }
        } catch (\Exception $e) {
            // Log del error pero no fallar la operación
            Log::error('Error enviando correo de entrevista: ' . $e->getMessage());
        }
        
        return redirect()->back()->with('success', 'Datos de la entrevista guardados correctamente y correo enviado al postulado.');
        } catch (\Exception $e) {
            Log::error('Error en guardarEntrevista: ' . $e->getMessage(), [
                'id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error al guardar la entrevista: ' . $e->getMessage());
        }
    }

    /**
     * Decidir (aprobar o rechazar) al postulado. Al aprobar, crear el monitor.
     */
    public function decidirEntrevista(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:aprobado,rechazado',
        ]);
        $postulado = \App\Models\Postulado::findOrFail($id);
        $monitoria = \App\Models\Monitoria::find($postulado->monitoria);
        if (!$monitoria || $monitoria->encargado != auth()->id()) {
            abort(403, 'No autorizado');
        }
        // Validar cupo de vacantes antes de aprobar
        if ($request->estado === 'aprobado') {
            $monitoresAprobados = \App\Models\Monitor::where('monitoria', $monitoria->id)
                ->where('estado', 'activo')
                ->count();
            if ($monitoresAprobados >= $monitoria->vacante) {
                return redirect()->back()->with('error_swal', 'No se puede aprobar: ya se alcanzó el número máximo de monitores para esta monitoría (' . $monitoria->vacante . ' vacantes).');
            }
            
            // Verificar si el usuario ya es monitor activo en otra monitoría
            $monitorEnOtraMonitoria = \App\Models\Monitor::where('user', $postulado->user)
                ->where('monitoria', '!=', $postulado->monitoria)
                ->where('estado', 'activo')
                ->first();

            if ($monitorEnOtraMonitoria) {
                return redirect()->back()->with('error_swal', 
                    'Este estudiante ya es monitor activo en otra monitoría.'
                );
            }
        }
        $postulado->estado = $request->estado;
        $postulado->save();
        if ($request->estado === 'aprobado') {
            $monitorExistente = \App\Models\Monitor::where('monitoria', $postulado->monitoria)->where('user', $postulado->user)->first();
            if (!$monitorExistente) {
                \App\Models\Monitor::create([
                    'user' => $postulado->user,
                    'monitoria' => $postulado->monitoria,
                    'fecha_vinculacion' => null,
                    'fecha_culminacion' => null
                ]);
            }
        } else {
            \App\Models\Monitor::where('user', $postulado->user)
                ->where('monitoria', $postulado->monitoria)
                ->delete();
        }
        return redirect()->back()->with('success', 'Decisión guardada correctamente.');
    }

    /**
     * Revertir la decisión sobre un postulado (solo encargado)
     */
    public function revertirDecision(Request $request, $id)
    {
        $postulado = \App\Models\Postulado::findOrFail($id);
        $monitoria = \App\Models\Monitoria::find($postulado->monitoria);
        if (!$monitoria || $monitoria->encargado != auth()->id()) {
            abort(403, 'No autorizado');
        }

        // Si estaba aprobado, elimina el monitor
        if ($postulado->estado === 'aprobado') {
            \App\Models\Monitor::where('user', $postulado->user)
                ->where('monitoria', $postulado->monitoria)
                ->delete();
        }

        // Cambia el estado a "aprobado_entrevista" (o "pendiente" si prefieres)
        $postulado->estado = 'aprobado_entrevista';
        $postulado->save();

        return redirect()->back()->with('success', 'La decisión ha sido revertida. Ahora puedes volver a decidir sobre este postulado.');
    }

}
