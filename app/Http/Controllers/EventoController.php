<?php

    namespace App\Http\Controllers;
    use Illuminate\Support\Facades\Mail;
    use Illuminate\Http\Request;
    use App\Models\Lugar;
    use App\Models\ProgramaDependencia;
    use App\Models\Espacio;
    use App\Models\Evento;
    use App\Models\DetallesEvento;
    use App\Models\InventarioEvento;
    use App\Models\Calificacion;
    use App\Models\Anotacion;
    use App\Models\User;
    use App\Http\Middleware\CheckRole;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;
    use Auth;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Storage;
    use App\Notifications\NuevoEventoAceptado;
    use App\Notifications\NuevoEvento;
    use App\Mail\AnotacionAgregadaMail; 
    use App\Notifications\RechazoEventoNotificacion;
    use Illuminate\Support\Facades\Notification;
    use Barryvdh\DomPDF\Pdf;
    use App\Exports\EventosExport;
    use Maatwebsite\Excel\Facades\Excel;
    class EventoController extends Controller
    {
        /**
         * Muestra el formulario para crear un nuevo evento.
         *
         * @return \Illuminate\Http\Response
         */
        public function crearEvento()
        {
            $lugares = Lugar::all();
            $programasDependencia = ProgramaDependencia::orderBy('nombrePD')->get();
            $espacios = Espacio::all();
            
            return view('crearEvento', compact('lugares', 'programasDependencia', 'espacios'));
        }

        /**
         * Guarda un nuevo evento en la base de datos.
         *
         * @param  \Illuminate\Http\Request  $request
         * @return \Illuminate\Http\Response
         */
        public function guardarEvento(Request $request)
        {
            // Validar los datos del formulario, incluyendo el archivo del flyer
            $request->validate([
                'nombreEvento' => 'required|string',
                'propositoEvento' => 'required|string',
                'dependenciasSeleccionadas' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $data = json_decode($value, true);
                        if (!is_array($data) || empty($data)) {
                            $fail('Debe seleccionar al menos una dependencia.');
                        }
                    }
                ],
                'lugar' => 'required|exists:lugar,id',
                'espacio' => 'required|exists:espacio,id',
                'fechaRealizacion' => 'required|date',
                'horaInicio' => 'required',
                'horaFin' => 'required',
                'flyer' => 'file|mimes:jpg,jpeg,png,bmp,gif,svg,webp,pdf,doc,docx,xls,xlsx',
            ]);
        
            // Decodificar las dependencias (ya validado que es array y no vacío)
            $dependencias = json_decode($request->input('dependenciasSeleccionadas'), true);
        
            // Iniciar la transacción
            DB::beginTransaction();

            try {
                // 1️⃣ Guardar evento principal
                $evento = new Evento();
                $evento->nombreEvento = $request->nombreEvento;
                $evento->propositoEvento = $request->propositoEvento;
                $evento->user = Auth::id();
                $evento->lugar = $request->lugar;
                $evento->espacio = $request->espacio;
                $evento->fechaRealizacion = $request->fechaRealizacion;
                $evento->horaInicio = $request->horaInicio;
                $evento->horaFin = $request->horaFin;
                $evento->estado = 'Creado';

                if ($request->hasFile('flyer')) {
                    $flyer = $request->file('flyer');
                    Log::info('Intentando subir el flyer.', [
                        'original_name' => $flyer->getClientOriginalName(),
                        'mime_type' => $flyer->getMimeType(),
                        'size' => $flyer->getSize(),
                    ]);

                    try {
                        $flyerPath = $flyer->store('flyers', 'public');
                        $evento->flyer = $flyerPath;
                        Log::info('Flyer subido correctamente: ' . $flyerPath);
                    } catch (\Exception $e) {
                        Log::error('Error al subir el flyer: ' . $e->getMessage(), [
                            'original_name' => $flyer->getClientOriginalName(),
                            'mime_type' => $flyer->getMimeType(),
                            'size' => $flyer->getSize(),
                        ]);
                        return back()->withErrors(['error' => 'Error al subir el archivo']);
                    }
                } else {
                    Log::info('No se ha proporcionado un flyer para subir.');
                }

                $evento->save();

                // 2️⃣ Guardar dependencias
                foreach ($dependencias as $dep) {
                    DB::table('evento_dependencia')->insert([
                        'evento_id' => $evento->id,
                        'programadependencia_id' => $dep['id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // 3️⃣ Guardar detalles del evento
                $detalleEvento = new DetallesEvento();
                $detalleEvento->evento = $evento->id;
                $detalleEvento->transporte = $request->input('transporte') === 'true';
                $detalleEvento->audio = $request->input('audio') === 'true';
                $detalleEvento->proyeccion = $request->input('proyeccion') === 'true';
                $detalleEvento->internet = $request->input('internet') === 'true';
                $detalleEvento->otros = $request->input('otros');
                $detalleEvento->diseñoPublicitario = $request->input('diseñoPublicitario') === 'true';
                $detalleEvento->redes = $request->input('redes') === 'true';
                $detalleEvento->correo = $request->input('correo') === 'true';
                $detalleEvento->whatsapp = $request->input('whatsapp') === 'true';
                $detalleEvento->personal_recibo = $request->input('personal_recibo') === 'true';
                $detalleEvento->seguridad = $request->input('seguridad') === 'true';
                $detalleEvento->bienvenida = $request->input('bienvenida') === 'true';
                $detalleEvento->defensoria_civil = $request->input('defensoria_civil') === 'true';
                $detalleEvento->certificacion = $request->input('certificacion') === 'true';
                $detalleEvento->cubrimiento_medios = $request->input('cubrimiento_medios') === 'true';
                $detalleEvento->servicio_general = $request->input('servicio_general') === 'true';
                $detalleEvento->otro_Recurso = $request->input('otro_Recurso') === 'true';
                $detalleEvento->estacion_bebidas = $request->input('estacion_bebidas') === 'true';
                $detalleEvento->presentacion_cultural = $request->input('presentacion_cultural') === 'true';
                $detalleEvento->estudiantes = $request->input('estudiantes') === 'true';
                $detalleEvento->profesores = $request->input('profesores') === 'true';
                $detalleEvento->administrativos = $request->input('administrativos') === 'true';
                $detalleEvento->empresarios = $request->input('empresarios') === 'true';
                $detalleEvento->comunidad_general = $request->input('comunidad_general') === 'true';
                $detalleEvento->egresados = $request->input('egresados') === 'true';
                $detalleEvento->invitados_externos = $request->input('invitados_externos') === 'true';
                $detalleEvento->save();

                // 4️⃣ Guardar inventario si aplica
                if ($request->has('requiereInventario')) {
                    $tiposInventario = $request->input('tipoInventario');
                    $cantidadesInventario = $request->input('cantidadInventario');
                    foreach ($tiposInventario as $index => $tipo) {
                        $inventarioEvento = new InventarioEvento();
                        $inventarioEvento->evento = $evento->id;
                        $inventarioEvento->tipo = $tipo;
                        $inventarioEvento->cantidad = $cantidadesInventario[$index];
                        $inventarioEvento->save();
                    }
                }

                // 5️⃣ Notificar al creador y a un correo adicional
                $usuarioCreador = Auth::user();
                $correoAdicional = 'chaosplayzmc1@gmail.com';

                Notification::send($usuarioCreador, new NuevoEvento($evento));
                Notification::route('mail', $correoAdicional)->notify(new NuevoEvento($evento));

                // ✅ TODO OK: commit y redirect
                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al guardar el evento y sus detalles: ' . $e->getMessage());
                return back()->withErrors(['error' => 'Error al guardar el evento y sus detalles.']);
            }

        }

        public function verEvento($id)
        {
            try {
                // Obtener el evento por su ID
                $evento = Evento::findOrFail($id);
        
                // Obtener el nombre del lugar asociado al evento si existe
                $nombreLugar = null;
                $lugar = Lugar::find($evento->lugar);
                if ($lugar) {
                    $nombreLugar = $lugar->nombreLugar;
                }
        
                // Obtener el nombre del espacio asociado al evento si existe
                $nombreEspacio = null;
                $espacio = Espacio::find($evento->espacio);
                if ($espacio) {
                    $nombreEspacio = $espacio->nombreEspacio;
                }
        
                // Obtener todas las dependencias asociadas al evento desde la tabla evento_dependencia
                $dependencias = DB::table('evento_dependencia')
                    ->where('evento_id', $evento->id)
                    ->pluck('programadependencia_id');
        
                // Inicializar los nombres de las dependencias
                $nombresDependencias = [];
        
                foreach ($dependencias as $dependenciaId) {
                    $programaDependencia = ProgramaDependencia::find($dependenciaId);
                    if ($programaDependencia) {
                        $nombresDependencias[] = $programaDependencia->nombrePD;
                    }
                }
        
                // Obtener los detalles del evento asociados al evento
                $detallesEvento = DetallesEvento::where('evento', $id)->first();
        
                // Obtener el inventario del evento asociado al evento
                $inventarioEvento = InventarioEvento::where('evento', $id)->get();
        
                // Formatear la información
                $informacionEvento = [
                    'evento' => $evento,
                    'detallesEvento' => $detallesEvento,
                    'inventarioEvento' => $inventarioEvento,
                    'lugar' => $nombreLugar,
                    'espacio' => $nombreEspacio,
                    'dependencias' => $nombresDependencias, // ✅ ahora bien hecho desde evento_dependencia
                ];
        
                // Devolver la información en formato JSON
                return response()->json($informacionEvento);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error al obtener la información del evento'], 500);
            }
        }
        

        public function informacionCorreo()
        {
            // Obtener todos los eventos
            $eventos = Evento::all();

            $nombresEventos = [];

            foreach ($eventos as $evento) {
                // Obtener el nombre del espacio
                $espacio = Espacio::find($evento->espacio);
                $nombreEspacio = $espacio ? $espacio->nombreEspacio : null;

                // Obtener las dependencias relacionadas desde la tabla pivot
                $dependenciaIds = DB::table('evento_dependencia')
                                    ->where('evento_id', $evento->id)
                                    ->pluck('programadependencia_id');

                // Obtener los nombres de esas dependencias
                $nombresDependencias = ProgramaDependencia::whereIn('id', $dependenciaIds)
                                        ->pluck('nombrePD')
                                        ->toArray();

                // Guardar la info en el arreglo final
                $nombresEventos[] = [
                    'evento' => $evento->nombreEvento,
                    'nombreEspacio' => $nombreEspacio,
                    'nombresDependencias' => $nombresDependencias, // array de nombres
                ];
            }

            return $nombresEventos;
        }

        public function mostrarEvento()
        {
            // Obtener la información
            $nombresEventos = $this->informacionCorreo(); // Llamamos el método que hemos creado

            // Pasar la información a la vista Blade
            return view('emails.eventos-del-dia', ['nombresEventos' => $nombresEventos]);
        }
        public function indexAdmin()
        {
            $user = auth()->user(); // Usuario autenticado

            if ($user->hasRole(['CooAdmin', 'AuxAdmin', 'Administrativo'])) {
                // Mostrar todos los eventos para roles administrativos
                $eventos = Evento::all();
            } elseif ($user->hasRole('Profesor')) {
                // Mostrar eventos según si el usuario tiene dependencia o no
                if ($user->dependencia_id !== null) {
                    // Filtrar eventos según la tabla evento_dependencia
                    $eventos = Evento::whereHas('dependencias', function ($query) use ($user) {
                        $query->where('programadependencia_id', $user->dependencia_id);
                    })->orWhere('user', $user->id)->get();
                } else {
                    // Solo eventos creados por el usuario si no tiene dependencia
                    $eventos = Evento::where('user', $user->id)->get();
                }
            } else {
                return redirect()->back()->with('error', 'No tienes permiso para ver estos eventos.');
            }

            $lugares = Lugar::all();

            // Asignar nombre del lugar a cada evento
            foreach ($eventos as $evento) {
                $nombreLugar = $lugares->firstWhere('id', $evento->lugar)->nombreLugar ?? 'Lugar no encontrado';
                $evento->nombreLugar = $nombreLugar;
            }

            // ✅ Filtrar eventos aceptados según el estado del enum
            $eventosAceptados = $eventos->filter(function ($evento) {
                return $evento->estado === 'Aceptado'; // Asegúrate que 'aceptado' coincide con tu enum
            });
            

            // ✅ Cargar usuarios autorizados para envío de correos
            $correosPermitidos = [
                'logistica1@univalle.edu.co',
                'servicios1@univalle.edu.co',
                'logistica2@univalle.edu.co',
                'sebastian.gply@gmail.com',
                'georsuans.giraldo@correounivalle.edu.co'
                // Agrega aquí los correos reales
            ];
            $usuarios = \App\Models\User::whereIn('email', $correosPermitidos)->get();

            return view('roles.administrativo.consultarEventos', compact('eventos', 'eventosAceptados', 'usuarios'));
        }


        

        
        

    /**
     * Obtiene los eventos en el formato necesario para FullCalendar.
     *
     * @return \Illuminate\Http\Response
     */
        public function obtenerEventos()
        {
            $eventos = Evento::where('estado', 'Aceptado')->get();
            $lugares = Lugar::all();
            $espacios = Espacio::all();
            
            $eventosFormateados = [];
            foreach ($eventos as $evento) {
                // Buscar el nombre del lugar asociado al evento
                $nombreLugar = $lugares->firstWhere('id', $evento->lugar)->nombreLugar;

                // Buscar el nombre del espacio asociado al evento
                $nombreEspacio = $espacios->firstWhere('id', $evento->espacio)->nombreEspacio;

                $eventoFormateado = [
                    'title' => $evento->nombreEvento,
                    'start' => $evento->fechaRealizacion . 'T' . $evento->horaInicio,
                    'end' => $evento->fechaRealizacion . 'T' . $evento->horaFin,
                    'lugar' => $nombreLugar,
                    'espacio' => $nombreEspacio,
                ];
                array_push($eventosFormateados, $eventoFormateado);
            }

            return response()->json($eventosFormateados);
        }

        
        public function editarEvento($id)
        {
            // Busca el evento por su ID
            $evento = Evento::find($id);
        
            // Verificar si el evento existe
            if (!$evento) {
                // Manejar el caso de que el evento no exista
                return redirect()->route('eventos.index')->with('error', 'El evento no fue encontrado.');
            }
        
            // Obtener el usuario autenticado
            $user = auth()->user();
        
            // Verificar si el usuario tiene permisos para editar este evento
            if (($user->rol !== 'CooAdmin') && ($user->rol !== 'AuxAdmin') && ($user->rol !== 'Administrativo') && $evento->user !== $user->id) {
                // Si no tiene permiso, redirigir con un mensaje de error
                abort(403, 'No tienes permiso para editar este evento.');
            }
        
            // Busca los detalles del evento por el ID del evento
            $detallesEvento = DetallesEvento::where('evento', $id)->first();
        
            // Obtener el inventario del evento asociado al evento
            $inventarioEvento = InventarioEvento::where('evento', $id)->get();
        
            // Busca todos los lugares y espacios
            $lugares = Lugar::all();
            $espacios = Espacio::all();
        
            // Busca todas las dependencias asociadas al evento a través de la tabla evento_dependencia
            $dependenciasEvento = DB::table('evento_dependencia')
            ->join('programadependencia', 'evento_dependencia.programadependencia_id', '=', 'programadependencia.id')
            ->where('evento_id', $id)
            ->select('programadependencia.id', 'programadependencia.nombrePD')
            ->get();
        
            // Busca todos los programas de dependencia disponibles
            $programasDependencia = ProgramaDependencia::orderBy('nombrePD')->get();
        
            // Retorna la vista de edición del evento, pasando la información necesaria
            return view('editarEvento', [
                'evento' => $evento,
                'lugares' => $lugares,
                'espacios' => $espacios,
                'programasDependencia' => $programasDependencia,
                'detallesEvento' => $detallesEvento,
                'inventarioEvento' => $inventarioEvento,
                'dependenciasEvento' => $dependenciasEvento, // Pasamos las dependencias seleccionadas
            ]);
        }
        
        public function verificarNombre(Request $request)
        {
            $nombre = $request->nombre;
            $eventoId = $request->evento_id;
            $espacio = $request->espacio;
            $fechaRealizacion = $request->fecha_realizacion;

            $existe = Evento::where('nombreEvento', $nombre)
                            ->where('id', '!=', $eventoId) // Excluir el evento actual
                            ->where('espacio', $espacio) // Verificar solo en el mismo espacio
                            ->where('estado', '!=', 'Cerrado') // Excluir eventos cerrados
                            ->where('fechaRealizacion', $fechaRealizacion) // Verificar la misma fecha
                            ->exists();

            return response()->json(['existe' => $existe]);
        }

        public function actualizarEvento(Request $request, $id)
        {
            // Validar los datos del formulario
            $request->validate([
                'nombreEvento' => 'required|string',
                'propositoEvento' => 'required|string',
                'dependenciasSeleccionadas' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $data = json_decode($value, true);
                        if (!is_array($data) || empty($data)) {
                            $fail('Debe seleccionar al menos una dependencia.');
                        }
                    }
                ],
                'lugar' => 'required|exists:lugar,id',
                'espacio' => 'required|exists:espacio,id',
                'fechaRealizacion' => 'required|date',
                'horaInicio' => 'required',
                'horaFin' => 'required',
                'flyer' => 'file|mimes:jpg,jpeg,png,bmp,gif,svg,webp,pdf,doc,docx,xls,xlsx'
            ]);

            // Decodificar las dependencias (ya validado que es array y no vacío)
            $dependencias = json_decode($request->input('dependenciasSeleccionadas'), true);

            try {
                DB::beginTransaction();
        
                // Busca el evento por su ID
                $evento = Evento::findOrFail($id);
        
                // Verificar si el usuario desea eliminar el flyer
                if ($request->has('eliminar_flyer') && $evento->flyer) {
                    Storage::delete($evento->flyer);  // Eliminar el archivo del almacenamiento
                    $evento->flyer = null;  // Eliminar la referencia del flyer en la base de datos
                }
                
                // Verificar si hay un nuevo flyer y subirlo
                if ($request->hasFile('flyer')) {
                    // Elimina el flyer anterior si es necesario (opcional)
                    if ($evento->flyer) {
                        Storage::delete($evento->flyer);
                    }
                
                    // Subir el nuevo flyer
                    $flyerPath = $request->file('flyer')->store('flyers');
                
                    // Actualizar el campo de flyer en el evento con el path completo
                    $evento->flyer = $flyerPath; // Guarda la ruta completa
                }


        
                // Actualiza los campos del evento con los datos del formulario
                $evento->nombreEvento = $request->nombreEvento;
                $evento->propositoEvento = $request->propositoEvento;
                $evento->lugar = $request->lugar;
                $evento->espacio = $request->espacio;
                $evento->fechaRealizacion = $request->fechaRealizacion;
                $evento->horaInicio = $request->horaInicio;
                $evento->horaFin = $request->horaFin;
                $evento->save();


                DB::table('evento_dependencia')->where('evento_id', $evento->id)->delete();

                // Guardar las nuevas dependencias
                foreach ($dependencias as $dep) {
                    DB::table('evento_dependencia')->insert([
                        'evento_id' => $evento->id,
                        'programadependencia_id' => $dep['id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
        

                // Actualiza los detalles del evento
                $detallesEvento = DetallesEvento::where('evento', $id)->first();
                if ($detallesEvento) {
                    $detallesEvento->transporte = $request->input('transporte') === 'true' ? true : false;
                    $detallesEvento->audio = $request->input('audio') === 'true' ? true : false;
                    $detallesEvento->proyeccion = $request->input('proyeccion') === 'true' ? true : false;
                    $detallesEvento->internet = $request->input('internet') === 'true' ? true : false;
                    $detallesEvento->otros = $request->input('otros');
                    $detallesEvento->diseñoPublicitario = $request->input('diseñoPublicitario') === 'true' ? true : false;
                    $detallesEvento->redes = $request->input('redes') === 'true' ? true : false;
                    $detallesEvento->correo = $request->input('correo') === 'true' ? true : false;
                    $detallesEvento->whatsapp = $request->input('whatsapp') === 'true' ? true : false;
                    $detallesEvento->personal_recibo = $request->input('personal_recibo') === 'true' ? true : false;
                    $detallesEvento->seguridad = $request->input('seguridad') === 'true' ? true : false;
                    $detallesEvento->bienvenida = $request->input('bienvenida') === 'true' ? true : false;
                    $detallesEvento->defensoria_civil = $request->input('defensoria_civil') === 'true' ? true : false;
                    $detallesEvento->certificacion = $request->input('certificacion') === 'true' ? true : false;
                    $detallesEvento->cubrimiento_medios = $request->input('cubrimiento_medios') === 'true' ? true : false;
                    $detallesEvento->servicio_general = $request->input('servicio_general') === 'true' ? true : false;
                    $detallesEvento->otro_Recurso = $request->input('otro_Recurso') === 'true' ? true : false; 
                    $detallesEvento->estacion_bebidas = $request->input('estacion_bebidas') === 'true' ? true : false;
                    $detallesEvento->presentacion_cultural = $request->input('presentacion_cultural') === 'true' ? true : false;

                    $detallesEvento->estudiantes = $request->input('estudiantes') === 'true' ? true : false;
                    $detallesEvento->profesores = $request->input('profesores') === 'true' ? true : false;
                    $detallesEvento->administrativos = $request->input('administrativos') === 'true' ? true : false;
                    $detallesEvento->empresarios = $request->input('empresarios') === 'true' ? true : false;
                    $detallesEvento->comunidad_general = $request->input('comunidad_general') === 'true' ? true : false;
                    $detallesEvento->egresados = $request->input('egresados') === 'true' ? true : false;
                    $detallesEvento->invitados_externos = $request->input('invitados_externos') === 'true' ? true : false;
                    $detallesEvento->save();
                            
                }

                
                // Actualiza el inventario del evento si es necesario
                if ($request->has('requiereInventario')) {
                    // Elimina el inventario anterior del evento
                    InventarioEvento::where('evento', $id)->delete();

                    // Obtener los tipos de inventario y las cantidades del formulario
                    $tiposInventario = $request->input('tipoInventario');
                    $cantidadesInventario = $request->input('cantidadInventario');
                
                    // Iterar sobre los tipos de inventario y las cantidades y guardar cada elemento de inventario en la base de datos
                    foreach ($tiposInventario as $index => $tipo) {
                        $inventarioEvento = new InventarioEvento();
                        $inventarioEvento->evento = $evento->id;
                        $inventarioEvento->tipo = $tipo;
                        $inventarioEvento->cantidad = $cantidadesInventario[$index];
                        $inventarioEvento->save();
                    }
                }

                DB::commit();

                return redirect()->back()->with('success', 'Evento actualizado correctamente');
            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()->back()->with('error', 'Error al actualizar el evento');
            }
        }

        public function informacionEventos()
        {
            $eventos = Evento::all();
            $lugares = Lugar::all();
            $espacios = Espacio::all();

            $data = [
                'eventos' => $eventos,
                'lugares' => $lugares,
                'espacios' => $espacios,
            ];

            return response()->json($data);
        }
        
        

        public function actualizarEstado(Request $request, $eventoId)
        {
            $request->validate([
                'estado' => 'required|in:Creado,Aceptado,Rechazado,Cancelado,Cerrado',
            ]);

            try {
                DB::beginTransaction();

                // Encuentra el evento y actualiza su estado
                $evento = Evento::findOrFail($eventoId);
                $evento->estado = $request->estado;
                $evento->save();

                DB::commit();

                // Obtener el usuario creador del evento
                $usuarioCreador = User::find($evento->user);
                if ($usuarioCreador) {
                    if ($evento->estado === 'Aceptado') {
                        $notificacionAceptado = new NuevoEventoAceptado($evento);
                        Notification::route('mail', $usuarioCreador->email)->notify($notificacionAceptado);

                        // Correos adicionales
                        $correosAdicionales = ['chaosplayzmc1@gmail.com']; //aqui poner la lista de correos pero falta confirmar los correos
                        foreach ($correosAdicionales as $correo) {
                            Notification::route('mail', $correo)->notify($notificacionAceptado);
                        }
                    } elseif ($evento->estado === 'Rechazado') {
                        $notificacionRechazado = new RechazoEventoNotificacion($evento);
                        Notification::route('mail', $usuarioCreador->email)->notify($notificacionRechazado);
                        
                        \Log::info('Notificación de rechazo enviada a: ' . $usuarioCreador->email);
                    }
                }

                // Cambiar la respuesta a JSON
                return response()->json(['success' => true, 'message' => 'Estado del evento actualizado y notificación enviada.']);
            } catch (\Exception $e) {
                DB::rollBack();
                // Devolver JSON en caso de error
                return response()->json(['success' => false, 'message' => 'Error al actualizar el estado del evento'], 500);
            }
        }


    public function borrarEvento($id)
    {
        try {
            DB::beginTransaction();
    
            // Encuentra el evento por su ID
            $evento = Evento::findOrFail($id);
    
            // Borra los detalles del evento asociados a este evento
            DetallesEvento::where('evento', $evento->id)->delete();
    
            // Borra los elementos de inventario asociados a este evento
            InventarioEvento::where('evento', $evento->id)->delete();
    
            // Borra el evento
            $evento->delete();
    
            DB::commit();
    
            // Redirecciona de vuelta con un mensaje de éxito
            return redirect()->back()->with('success', 'Evento borrado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
    
            // Redirecciona de vuelta con un mensaje de error
            return redirect()->back()->with('error', 'Error al borrar el evento');
        }
    }

        /**
         * Obtiene los espacios asociados a un lugar específico.
         *
         * @param  int  $lugarId
         * @return \Illuminate\Http\Response
         */
        
        public function obtenerEspacios($lugarId)
        {
            $espacios = Espacio::where('lugar', $lugarId)->get();
            return response()->json($espacios);
        }

    
        public function calificarEvento(Request $request)
        {
            // Validar los datos recibidos del formulario
            $validatedData = $request->validate([
                'evento_id' => 'required|exists:evento,id',
                'puntaje' => 'required|integer|min:1|max:5',
                'comentario' => 'nullable|string|max:255',
                'asistentes' => 'required|integer',
            ]);

            // Crear una nueva instancia de Calificacion
            $calificacion = new Calificacion();
            $calificacion->evento_id = $validatedData['evento_id']; // Asignar el ID del evento
            $calificacion->puntaje = $validatedData['puntaje']; // Asignar el puntaje recibido
            $calificacion->comentario = $validatedData['comentario']; // Asignar el comentario, si está presente
            $calificacion->asistentes = $validatedData['asistentes'];
            $calificacion->save();
    
            return response()->json(['success' => true]);
        }

        public function verificarCalificacion($eventoId)
        {
            $calificacion = Calificacion::where('evento_id', $eventoId)->first();
        
            if ($calificacion) {
                return response()->json([
                    'calificado' => true,
                    'calificacion' => $calificacion->puntaje, // Ajusta el campo según tu modelo
                    'comentario' => $calificacion->comentario,
                    'asistentes' => $calificacion->asistentes
                    
                ]);
            }
        
            return response()->json(['calificado' => false]);
        }

        public function agregarAnotacion(Request $request)
    {
        try {
            // Validar los datos del formulario
            $request->validate([
                'evento_id' => 'required|exists:evento,id', // Asegúrate de que la tabla se llame 'eventos'
                'contenido' => 'required|string',
                'archivo' => 'nullable|file|mimes:jpg,jpeg,png,bmp,gif,svg,webp,pdf,doc,docx,xls,xlsx'
            ]);

            // Crear una nueva instancia de Anotacion
            $anotacion = new Anotacion();
            $anotacion->evento_id = $request->evento_id;
            $anotacion->usuario_id = Auth::id();
            $anotacion->contenido = $request->contenido;
            $anotacion->fecha = now();

            // Manejar la carga del archivo
            if ($request->hasFile('archivo')) {
                $archivo = $request->file('archivo');
                $archivoPath = $archivo->store('anotaciones', 'public');
                // Guarda el archivo en el disco 'public' en la carpeta 'anotaciones'
                $anotacion->archivo = $archivoPath;
            }

            $anotacion->save();

            // Obtener el evento y el creador
            $evento = Evento::find($request->evento_id);
            $creador = User::find($evento->user);

            // Crear una lista de correos para enviar
            $correosAdicionales = ['chaosplayzmc1@gmail.com', 'georsuans.giraldo@correounivalle.edu.co']; // Agrega más correos según necesites
            $correos = array_merge([$creador->email], $correosAdicionales);

            // Enviar el correo
            Mail::to($correos)->send(new AnotacionAgregadaMail($anotacion, $evento->nombreEvento)); // Asegúrate de pasar el nombre del evento

            // Devolver una respuesta JSON con éxito y un mensaje específico
            return response()->json(['success' => true, 'message' => 'Anotación agregada correctamente y correo enviado.']);
        } catch (\Exception $e) {
            // Capturar la excepción y devolver una respuesta de error
            return response()->json(['success' => false, 'message' => 'Error al agregar la anotación: ' . $e->getMessage()]);
        }
    }

    

        
        
        public function verAnotaciones($eventoId)
        {
            // Obtener las anotaciones para el evento específico incluyendo el nombre del usuario
            $anotaciones = Anotacion::where('evento_id', $eventoId)
                ->with('usuario:id,name') // Cargar relación 'usuario' con solo id y nombre
                ->get()
                ->map(function ($anotacion) {
                    // Generar la URL directamente desde 'public/storage'
                    $anotacion->archivo_url = $anotacion->archivo ? url('storages/' . $anotacion->archivo) : null;
                    return $anotacion;
                });
        
        
            // Retornar las anotaciones con los nombres de usuario
            return response()->json($anotaciones);
        }
        
        public function exportarEventos(Request $request)
        {
            // Obtener fechas de inicio y fin desde el formulario
            $fechaInicio = $request->input('fechaInicio');
            $fechaFin = $request->input('fechaFin');
        
            // Filtrar los eventos dentro del rango de fechas especificado
            $eventos = Evento::whereBetween('fechaRealizacion', [$fechaInicio, $fechaFin])->get();
        
            // Verificar si hay eventos; si no, redirigir con mensaje de alerta
            if ($eventos->isEmpty()) {
                return redirect()->back()->with('no_eventos', 'No se encontraron eventos en el rango de fechas seleccionado.');
            }
        
            // Crear una instancia del exportador y pasarle la colección de eventos filtrada
            return Excel::download(new EventosExport($eventos), 'listadoeventos.xlsx');
        }
    
    }

