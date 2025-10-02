<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventoController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\PeriodoAcademicoController;
use App\Http\Controllers\ConvocatoriaController;
use App\Http\Controllers\MonitoriaController;
use App\Http\Controllers\PostuladoController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\NovedadController;
use App\Http\Controllers\SubsidioAlimenticioController;
use App\Http\Controllers\ConvocatoriaSubsidioController;
use App\Http\Controllers\EstudianteConvocatoriaController;
use App\Http\Controllers\PostulacionSubsidioController;
use App\Http\Controllers\EstudiantePostulacionController;
use App\Http\Controllers\AdminPostulacionSubsidioController;
use App\Http\Controllers\AdminEstudiantesController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes();

Route::get('/', function () {
    return view('welcome');
});

Route::get('storage-link', function() {
    Artisan::call('storage:link');
});
Route::get('eventos-del-dia', function () {
    $key = request('key');

    if ($key !== env('EVENTOS_SECRET_KEY')) {
        abort(403, 'No autorizado.');
    }

    Artisan::call('eventos:enviar-del-dia');
    return 'Tarea ejecutada';
});

Route::get('/home', function () {
    $user = auth()->user();

    if ($user->hasRole('Administrativo') || $user->hasRole('CooAdmin') || $user->hasRole('AuxAdmin') ) {
        return redirect()->route('administrativo.dashboard');
    } elseif ($user->hasRole('Estudiante')) {
        return redirect()->route('estudiante.dashboard');
    } elseif ($user->hasRole('Profesor')) {
        return redirect()->route('profesor.dashboard');
    }

    // Opcional: redirigir a una página predeterminada si no se encuentra un rol
    return redirect('/'); // O la ruta que prefieras
});
Route::get('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

Route::middleware(['auth', 'checkrole:Administrativo,CooAdmin,AuxAdmin,Profesor', 'verified'])->group(function () {
    Route::get('/administrativo/dashboard', function () {
        return view('roles.administrativo.dashboard');
    })->name('administrativo.dashboard');

    Route::get('/consultarEventos', [EventoController::class, 'indexAdmin'])->name('consultarEventos');
});

Route::middleware(['auth', 'checkrole:Estudiante', 'verified'])->group(function () {
    Route::get('/estudiante/dashboard', function () {
        return view('roles.estudiante.dashboard');
    })->name('estudiante.dashboard');
});

Route::middleware(['auth', 'checkrole:Profesor', 'verified'])->group(function () {
    Route::get('/profesor/dashboard', function () {
        return view('roles.profesor.dashboard');
    })->name('profesor.dashboard');
    Route::get('/profesor/dashboard', [DashboardController::class, 'profesorDashboard'])->name('profesor.dashboard');


});

Route::middleware(['auth', 'checkrole:Administrativo,Profesor,CooAdmin,AuxAdmin', 'verified'])->group(function () {
    Route::get('/crearEvento', [EventoController::class, 'crearEvento'])->name('crearEvento');
    Route::get('/administrativo/dashboard', [DashboardController::class, 'administrativoDashboard'])->name('administrativo.dashboard');
    
});

Route::get('/obtener-espacios/{lugarId}', [EventoController::class, 'obtenerEspacios']);

Route::post('/crearEvento', [EventoController::class, 'guardarEvento'])->name('guardarEvento');

Route::post('/guardar-evento', [EventoController::class, 'guardarEvento'])->name('guardar-evento');
Route::get('/obtener-eventos', [EventoController::class, 'obtenerEventos']);
Route::get('generate-pdf/{id}', [PDFController::class, 'generatePDF']);

Route::get('/flush-session', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login')->with('success','Sesión reiniciada.');
});


Route::middleware(['auth'])->get('/calendario', function () {
    return view('calendario');
})->name('calendario');;

Route::post('/crearMonitor/{postuladoId}', [PostuladoController::class, 'storeFechas'])->name('postulados.storeFechas');


##
Route::post('/monitoria/seguimiento/store', [MonitoriaController::class, 'store'])->name('seguimiento.monitoria.store');
Route::get('/monitoria/seguimiento/{monitoria_id}', [MonitoriaController::class, 'seguimiento'])->name('seguimiento.monitoria');
Route::get('/monitor-id', [MonitorController::class, 'getMonitorId'])->name('monitor.id');
Route::get('/api/actividades/{monitor_id}', [MonitorController::class, 'cargarActividades']);
Route::delete('/api/actividades/eliminar/{id}', [MonitorController::class, 'eliminar'])->name('actividades.eliminar');
##

Route::put('/administrativo/consultarEventos/{eventoId}/actualizar-estado', [EventoController::class, 'actualizarEstado'])->name('actualizar_estado');


Route::post('/actualizar-evento/{eventoId}', [EventoController::class, 'actualizarEvento'])->name('actualizarEvento');
Route::get('/editarEvento/{id}', [EventoController::class, 'editarEvento'])->name('editarEvento');
Route::delete('/eventos/borrar/{id}', [EventoController::class, 'borrarEvento'])->name('borrarEvento');
Route::get('/obtener-informacion-evento/{id}', [EventoController::class, 'verEvento'])->name('obtener_informacion_evento');
Route::get('/ver-evento/{id}', [EventoController::class, 'verEvento'])->name('ver_evento');
Route::post('/eventos/{id}/actualizar', [EventoController::class, 'actualizarEvento'])->name('actualizarEvento');
Route::get('/obtener-info', [EventoController::class, 'informacionEventos'])->name('crear.evento');
Route::post('/eventos/verificar-nombre', [EventoController::class, 'verificarNombre']);


#calificar evento
Route::post('/calificar-evento', [EventoController::class, 'calificarEvento'])->name('calificar-evento');
Route::get('/verificar-calificacion/{eventoId}', [EventoController::class, 'verificarCalificacion']);

#anotaciones de evento
Route::post('/anotaciones/agregar', [EventoController::class, 'agregarAnotacion'])->name('anotacion.agregar');
Route::get('/anotaciones/{eventoId}', [EventoController::class, 'verAnotaciones'])->name('anotaciones.ver');


Route::middleware(['auth'])->get('/dashboard', function () {
    $user = Auth::user();
    if ($user->hasRole('Administrativo') || $user->hasRole('CooAdmin') || $user->hasRole('AuxAdmin')) {
        return redirect()->route('administrativo.dashboard');
    } elseif ($user->hasRole('Profesor')) {
        return redirect()->route('profesor.dashboard');
    } elseif ($user->hasRole('Estudiante')) {
        return redirect()->route('estudiante.dashboard');
    } else {
        // Manejo para otros roles o situación no especificada
    }
})->name('dashboard');

Route::get('/exportar-eventos', [EventoController::class, 'exportarEventos'])->name('exportar.eventos');



//Modulo de monitorias
Route::middleware(['auth', 'checkrole:CooAdmin'])->group(function () {
    // Rutas de convocatorias
    Route::prefix('convocatorias')->group(function () {
        Route::get('/', [ConvocatoriaController::class, 'index'])->name('convocatoria.index');
        Route::post('/store', [ConvocatoriaController::class, 'store'])->name('convocatorias.store');
        Route::put('/{convocatoria}', [ConvocatoriaController::class, 'update'])->name('convocatorias.update');
        Route::delete('/{convocatoria}', [ConvocatoriaController::class, 'destroy'])->name('convocatorias.destroy');
        Route::get('/{convocatoria}', [ConvocatoriaController::class, 'show'])->name('convocatorias.show');
        Route::post('/{convocatoria}/reabrir', [ConvocatoriaController::class, 'reabrir'])->name('convocatorias.reabrir');
    });

    // Rutas de períodos académicos
    Route::get('/crearPeriodoA', [PeriodoAcademicoController::class, 'create'])->name('periodos.crear');
    Route::post('/crearPeriodoA', [PeriodoAcademicoController::class, 'store'])->name('periodos.store');
    Route::get('/obtenerPeriodo', [PeriodoAcademicoController::class, 'index'])->name('periodos.index');
    Route::put('/periodos/{periodoAcademico}', [PeriodoAcademicoController::class, 'update'])->name('periodos.update');
});
Route::middleware(['auth', 'checkrole:CooAdmin,Profesor,Administrativo'])->group(function () {
    Route::get('/monitorias', [MonitoriaController::class, 'index'])->name('monitoria.index');
    Route::post('/monitorias', [MonitoriaController::class, 'store'])->name('monitoria.store');
    Route::post('/monitorias/updateEstado/{id}', [MonitoriaController::class, 'updateEstado'])->name('monitorias.updateEstado');
    Route::put('/monitorias/actualizar', [MonitoriaController::class, 'update'])->name('monitoria.update');
    Route::get('/monitoria/get', [MonitoriaController::class, 'getMonitoria'])->name('monitoria.get');
    Route::get('/monitorias/activas', [MonitoriaController::class, 'listarMonitoriasActivas'])->name('monitorias.activas');
    


    
    
});

Route::middleware(['auth'])->group(function () {
    Route::post('/postular', [PostuladoController::class, 'store'])->name('postular');
    Route::delete('/postulacion/{monitoria}', [PostuladoController::class, 'destroy'])->name('postulacion.destroy');

    Route::get('/documentos/{monitoriaId}', [PostuladoController::class, 'getDocument']);
    Route::get('/monitorias/pdf', [MonitoriaController::class, 'generarPDF'])->name('monitorias.pdf');
    Route::put('/postulados/{id}', [PostuladoController::class, 'update'])->name('postulados.update');
    Route::get('/monitorias/lista', [MonitoriaController::class, 'listarMonitoriasActivas'])->name('monitorias.activas');
    Route::get('/monitorias/activas', function () {
        return view('monitoria.listaMonitorias');
    })->name('listaMonitorias');
    Route::post('/postulados/{id}/enviarCorreo', [PostuladoController::class, 'enviarCorreo'])->name('postulados.enviarCorreo');
    
    Route::get('/generate-pdf/{id}', [PDFController::class, 'generatePDF']);
    Route::post('/enviar-correo-evento', [PDFController::class, 'enviarCorreo'])->name('enviar.correo.evento');

    Route::get('gestion-monitores', [MonitorController::class, 'indexGestionMonitores'])->name('admin.gestionMonitores');
    Route::get('gestion-monitores/data', [MonitorController::class, 'getGestionMonitoresData'])->name('admin.gestionMonitores.data');
    Route::post('/gestion-monitores/store', [MonitorController::class, 'storeGestionMonitores'])->name('gestionMonitores.store');
    Route::get('/gestion-monitores/historico', [MonitorController::class, 'descargarHistorico'])->name('gestionMonitores.historico');
    
    // Rutas para lista de admitidos
    
    Route::get('/lista-admitidos/pdf', [App\Http\Controllers\ListaAdmitidosController::class, 'generarPDF'])->name('lista-admitidos.pdf');

    // Rutas para gestión de cédulas
    Route::get('/lista-admitidos', [App\Http\Controllers\ListaAdmitidosController::class, 'index'])->name('lista-admitidos.index');
    Route::post('/lista-admitidos/actualizar-cedulas', [App\Http\Controllers\ListaAdmitidosController::class, 'actualizarCedulas'])->name('lista-admitidos.actualizar-cedulas');



    Route::get('/monitoria/desempeno/pdf/{monitor_id}', [MonitoriaController::class, 'generarPDFDesempeno'])->name('monitoria.desempeno.pdf');
    Route::post('/monitoria/desempeno/guardar', [MonitoriaController::class, 'guardarDesempeno'])->name('monitoria.desempeno.guardar');
    Route::post('/monitoria/desempeno/borrar', [MonitoriaController::class, 'borrarDesempeno'])->name('monitoria.desempeno.borrar');

    Route::post('/monitoria/seguimiento/guardar', [MonitoriaController::class, 'guardarSeguimiento'])->name('monitoria.seguimiento.guardar');
    Route::post('/seguimiento/guardar-observacion/{id}', [MonitorController::class, 'guardarObservacion'])->name('seguimiento.guardarObservacion');

    Route::get('/monitoria/seguimiento/pdf/{monitor_id}/{mes}', [MonitoriaController::class, 'generarPDFSeguimiento'])->name('monitoria.seguimiento.pdf');
    // Rutas para asistencia mensual de monitoría (subir y ver)
    Route::post('/monitoria/asistencia/subir', [MonitorController::class, 'subirAsistencia'])->name('monitoria.asistencia.subir');
    Route::get('/monitoria/asistencia/ver/{monitor_id}/{anio}/{mes}', [MonitorController::class, 'verAsistencia'])->name('monitoria.asistencia.ver');
    Route::delete('/monitoria/asistencia/borrar/{monitor_id}/{anio}/{mes}', [App\Http\Controllers\MonitorController::class, 'borrarAsistencia'])->name('monitoria.asistencia.borrar');
});



Route::middleware(['auth', 'checkrole:Administrativo,CooAdmin,AuxAdmin'])->group(function () {
    Route::get('/postulados', [PostuladoController::class, 'index'])->name('postulados.index');
});
Route::middleware(['auth', 'checkrole:Administrativo,CooAdmin,AuxAdmin,Profesor'])->group(function () {

    Route::get('/postulados/entrevistas', [PostuladoController::class, 'entrevistas'])->name('postulados.entrevistas');
    Route::post('/postulados/{id}/guardar-entrevista', [PostuladoController::class, 'guardarEntrevista'])->name('postulados.guardarEntrevista');
    Route::post('/postulados/{id}/decidir-entrevista', [PostuladoController::class, 'decidirEntrevista'])->name('postulados.decidirEntrevista');
    Route::post('/postulados/{id}/revertir-decision', [PostuladoController::class, 'revertirDecision'])->name('postulados.revertirDecision');
});

Route::post('/monitorias/{id}/comentarios', [MonitoriaController::class, 'updateComentarios'])->name('monitorias.updateComentarios');

// Endpoint público para estadísticas de monitorías por modalidad (para dashboards)
Route::get('/convocatoria/estadisticas-monitorias', [ConvocatoriaController::class, 'estadisticasMonitorias']);

// Endpoint público para estadísticas de horas solicitadas vs aceptadas (para dashboards)
Route::get('/dashboard/estadisticas-horas-convocatoria', [DashboardController::class, 'estadisticasHorasConvocatoria']);


Route::prefix('novedades')->name('novedades.')->group(function () {
    Route::get('/', [NovedadController::class, 'index'])->name('index');
    Route::post('/', [NovedadController::class, 'store'])->name('store');
    Route::get('/{id}', [NovedadController::class, 'show'])->name('show');
    Route::put('/{id}', [NovedadController::class, 'update'])->name('update');
    Route::post('/{id}/evidencia', [NovedadController::class, 'addEvidencia'])->name('addEvidencia');
    Route::post('/{id}/cerrar', [NovedadController::class, 'closeNovedad'])->name('close');
    Route::delete('/{id}', [NovedadController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/mantenimiento-realizado', [NovedadController::class, 'updateEstado'])->name('updateEstado');
});

//modulo de mantenimiento administracion de novedades
Route::middleware(['auth', 'checkrole:Administrativo,Profesor,CooAdmin,AuxAdmin'])->group(function () {
    Route::prefix('novedades')->name('novedades.')->group(function () {
        Route::get('/', [NovedadController::class, 'index'])->name('index');
        Route::post('/', [NovedadController::class, 'store'])->name('store');
        Route::get('/{id}', [NovedadController::class, 'show'])->name('show');
        Route::put('/{id}', [NovedadController::class, 'update'])->name('update');
        Route::post('/{id}/evidencia', [NovedadController::class, 'addEvidencia'])->name('addEvidencia');
        Route::post('/{id}/cerrar', [NovedadController::class, 'closeNovedad'])->name('close');
        Route::delete('/{id}', [NovedadController::class, 'destroy'])->name('destroy');
    });

}); // <-- Fin del grupo de rutas de AdminBienestar

Route::middleware(['auth', 'checkrole:CooAdmin,AuxAdmin'])->group(function () {
    // Rutas para plan de mantenimiento preventivo
    Route::prefix('mantenimiento')->name('mantenimiento.')->group(function () {
        Route::get('/', [App\Http\Controllers\MantenimientoController::class, 'index'])->name('index');
        Route::get('/crear', [App\Http\Controllers\MantenimientoController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\MantenimientoController::class, 'store'])->name('store');
        Route::get('/{actividad}', [App\Http\Controllers\MantenimientoController::class, 'show'])->name('show');
        Route::get('/{actividad}/editar', [App\Http\Controllers\MantenimientoController::class, 'edit'])->name('edit');
        Route::put('/{actividad}', [App\Http\Controllers\MantenimientoController::class, 'update'])->name('update');
        Route::delete('/{actividad}', [App\Http\Controllers\MantenimientoController::class, 'destroy'])->name('destroy');
        
        // Rutas adicionales
        Route::post('/{actividad}/marcar-realizada', [App\Http\Controllers\MantenimientoController::class, 'marcarRealizada'])->name('marcar-realizada');
        Route::post('/{actividad}/marcar-pendiente', [App\Http\Controllers\MantenimientoController::class, 'marcarPendiente'])->name('marcar-pendiente');
        Route::post('/semana/{semana}/marcar-ejecutada', [App\Http\Controllers\MantenimientoController::class, 'marcarSemanaEjecutada'])->name('semana.marcar-ejecutada');
        Route::post('/semana/{semana}/marcar-pendiente', [App\Http\Controllers\MantenimientoController::class, 'marcarSemanaPendiente'])->name('semana.marcar-pendiente');
        Route::post('/{actividad}/generar-semanas', [App\Http\Controllers\MantenimientoController::class, 'generarSemanas'])->name('generar-semanas');
        Route::post('/cargar-actividades-predeterminadas', [App\Http\Controllers\MantenimientoController::class, 'cargarActividadesPredeterminadas'])->name('cargar-predeterminadas');
        Route::delete('/eliminar-todas', [App\Http\Controllers\MantenimientoController::class, 'eliminarTodas'])->name('eliminar-todas');
        // Compatibilidad: permitir también POST por si DELETE está bloqueado
        Route::post('/eliminar-todas', [App\Http\Controllers\MantenimientoController::class, 'eliminarTodas'])->name('eliminar-todas.post');
        Route::post('/limpiar-semanas', [App\Http\Controllers\MantenimientoController::class, 'limpiarSemanas'])->name('limpiar-semanas');
    });
    
    // Rutas para el sistema de evidencias de mantenimiento (por paquetes)
    Route::prefix('evidencias-mantenimiento')->name('evidencias-mantenimiento.')->group(function () {
        Route::get('/', [App\Http\Controllers\PaqueteEvidenciaController::class, 'index'])->name('index');
        Route::get('/paquetes/{paquete}/edit', [App\Http\Controllers\PaqueteEvidenciaController::class, 'edit'])->name('paquetes.edit');
        Route::put('/paquetes/{paquete}', [App\Http\Controllers\PaqueteEvidenciaController::class, 'update'])->name('paquetes.update');
        Route::get('/paquetes/{paquete}/generar-pdf', [App\Http\Controllers\PaqueteEvidenciaController::class, 'generarPdf'])->name('paquetes.generar-pdf');
        Route::get('/paquetes/{paquete}/descargar', [App\Http\Controllers\PaqueteEvidenciaController::class, 'descargar'])->name('paquetes.descargar');
        Route::get('/paquetes/{paquete}/previsualizar', [App\Http\Controllers\PaqueteEvidenciaController::class, 'previsualizar'])->name('paquetes.previsualizar');
        Route::delete('/paquetes/{paquete}/limpiar', [App\Http\Controllers\PaqueteEvidenciaController::class, 'eliminarPdf'])->name('paquetes.limpiar');
        Route::post('/limpiar-archivos', [App\Http\Controllers\PaqueteEvidenciaController::class, 'limpiarArchivos'])->name('limpiar-archivos');
    });
});

// Ruta para exportar Excel (sin middleware)
Route::get('/exportar-excel', [App\Http\Controllers\MantenimientoController::class, 'exportarExcel'])->name('mantenimiento.exportar-excel');

//administracion de usuarios
Route::middleware(['auth', 'checkrole:CooAdmin,AuxAdmin,Administrativo', 'verified'])->prefix('admin/usuarios')->name('admin.usuarios.')->group(function () {
    Route::get('/', [App\Http\Controllers\AdminUsuarioController::class, 'index'])->name('index');
    Route::post('/{id}/aprobar-rol', [App\Http\Controllers\AdminUsuarioController::class, 'aprobarRol'])->name('aprobarRol');
    Route::get('/crear', [App\Http\Controllers\AdminUsuarioController::class, 'create'])->name('create');
    Route::post('/crear', [App\Http\Controllers\AdminUsuarioController::class, 'store'])->name('store');
    Route::get('/{id}/editar', [App\Http\Controllers\AdminUsuarioController::class, 'edit'])->name('edit');
    Route::put('/{id}/actualizar', [App\Http\Controllers\AdminUsuarioController::class, 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\AdminUsuarioController::class, 'destroy'])->name('destroy');
});

//administracion de backups de base de datos
Route::middleware(['auth', 'verified'])->prefix('admin/backups')->name('admin.backups.')->group(function () {
    Route::get('/', [App\Http\Controllers\BackupController::class, 'index'])->name('index');
    Route::post('/crear', [App\Http\Controllers\BackupController::class, 'create'])->name('create');
    Route::get('/descargar/{filename}', [App\Http\Controllers\BackupController::class, 'download'])->name('download');
    Route::delete('/eliminar/{filename}', [App\Http\Controllers\BackupController::class, 'delete'])->name('delete');
});

Route::get('clear-cache', function() {
    try {
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        
        return response()->json([
            'success' => true,
            'message' => 'Caché limpiada correctamente',
            'commands' => [
                'view:clear' => 'Vistas limpiadas',
                'route:clear' => 'Rutas limpiadas', 
                'config:clear' => 'Configuración limpiada',
                'cache:clear' => 'Caché general limpiada'
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al limpiar caché: ' . $e->getMessage()
        ], 500);
    }
})->name('clear.cache');

// Rutas de verificación de correo electrónico
Route::get('/email/verify', function () {
    return view('auth.verify');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/dashboard');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '¡Enlace de verificación reenviado!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Ruta temporal para probar el sistema de convocatorias (ELIMINAR DESPUÉS DE PRUEBAS)
Route::get('/probar-convocatoria', function () {
    $convocatoria = \App\Models\Convocatoria::where('nombre', 'like', '%2025-II%')->first();
    
    if (!$convocatoria) {
        return response()->json(['error' => 'No se encontró la convocatoria'], 404);
    }
    
    $fechaAjustada = \App\Helpers\ConvocatoriaHelper::ajustarFechaCierre($convocatoria->fechaCierre);
    $estaAbierta = \App\Helpers\ConvocatoriaHelper::convocatoriaEstaAbierta($convocatoria->fechaCierre);
    $enEntrevistas = \App\Helpers\ConvocatoriaHelper::convocatoriaEnEntrevistas($convocatoria->fechaCierre, $convocatoria->fechaEntrevistas);
    $convocatoriaActiva = \App\Helpers\ConvocatoriaHelper::obtenerConvocatoriaActiva();
    
    $resultado = [
        'convocatoria' => [
            'nombre' => $convocatoria->nombre,
            'fecha_original' => $convocatoria->fechaCierre,
            'fecha_entrevistas' => $convocatoria->fechaEntrevistas,
        ],
        'hora_actual' => now()->format('Y-m-d H:i:s'),
        'helper' => [
            'fecha_ajustada' => $fechaAjustada->format('Y-m-d H:i:s'),
            'esta_abierta' => $estaAbierta,
            'en_entrevistas' => $enEntrevistas,
            'se_obtiene_como_activa' => $convocatoriaActiva ? true : false,
        ],
        'estado_sistema' => $estaAbierta ? 'ABIERTA' : ($enEntrevistas ? 'ENTREVISTAS' : 'FINALIZADA'),
        'funcionalidades' => [
            'se_pueden_postular' => $estaAbierta,
            'se_pueden_aprobar' => $estaAbierta,
            'se_muestran_monitorias' => $estaAbierta,
        ]
    ];
    
    return response()->json($resultado, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
})->name('probar.convocatoria');

// Ruta para ejecutar el comando ProbarSistemaCompleto desde el navegador
Route::get('/test-sistema-completo', function () {
    // Verificar clave de seguridad (opcional)
    $key = request('key');
    if ($key !== env('TEST_SECRET_KEY', 'test123')) {
        return response()->json(['error' => 'Clave de acceso requerida. Usar ?key=tu_clave'], 403);
    }
    
    try {
        // Capturar la salida del comando
        ob_start();
        
        // Ejecutar el comando y capturar su salida
        $exitCode = Artisan::call('convocatoria:probar-sistema');
        $output = Artisan::output();
        
        ob_end_clean();
        
        // Formatear la respuesta
        $response = [
            'success' => $exitCode === 0,
            'exit_code' => $exitCode,
            'output' => $output,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ];
        
        return response()->json($response, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Error ejecutando el comando: ' . $e->getMessage(),
            'timestamp' => now()->format('Y-m-d H:i:s')
        ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
})->name('test.sistema.completo');

// Ruta de diagnóstico para ver todas las convocatorias en la base de datos
Route::get('/debug-convocatorias', function () {
    // Verificar clave de seguridad (opcional)
    $key = request('key');
    if ($key !== env('TEST_SECRET_KEY', 'test123')) {
        return response()->json(['error' => 'Clave de acceso requerida. Usar ?key=tu_clave'], 403);
    }
    
    try {
        $convocatorias = \App\Models\Convocatoria::select('id', 'nombre', 'fechaCierre', 'fechaEntrevistas', 'created_at')
                                                  ->orderBy('created_at', 'desc')
                                                  ->get();
        
        $response = [
            'success' => true,
            'total_convocatorias' => $convocatorias->count(),
            'convocatorias' => $convocatorias->map(function($conv) {
                return [
                    'id' => $conv->id,
                    'nombre' => $conv->nombre,
                    'fecha_cierre' => $conv->fechaCierre,
                    'fecha_entrevistas' => $conv->fechaEntrevistas,
                    'creada_en' => $conv->created_at->format('Y-m-d H:i:s')
                ];
            }),
            'busqueda_actual' => 'like %2025-II%',
            'coincidencias' => $convocatorias->filter(function($conv) {
                return str_contains(strtolower($conv->nombre), '2025-ii');
            })->values(),
            'timestamp' => now()->format('Y-m-d H:i:s')
        ];
        
        return response()->json($response, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Error consultando convocatorias: ' . $e->getMessage(),
            'timestamp' => now()->format('Y-m-d H:i:s')
        ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
})->name('debug.convocatorias');

// Ruta alternativa para probar con convocatoria específica por parámetro
Route::get('/test-sistema-convocatoria', function () {
    // Verificar clave de seguridad (opcional)
    $key = request('key');
    if ($key !== env('TEST_SECRET_KEY', 'test123')) {
        return response()->json(['error' => 'Clave de acceso requerida. Usar ?key=tu_clave'], 403);
    }
    
    try {
        // Parámetros para personalizar la búsqueda
        $busqueda = request('buscar', '2025-II');
        $convocatoriaId = request('id');
        
        // Buscar convocatoria
        if ($convocatoriaId) {
            $convocatoria = \App\Models\Convocatoria::find($convocatoriaId);
        } else {
            $convocatoria = \App\Models\Convocatoria::where('nombre', 'like', "%{$busqueda}%")->first();
        }
        
        if (!$convocatoria) {
            // Mostrar convocatorias disponibles en caso de error
            $disponibles = \App\Models\Convocatoria::select('id', 'nombre')->get();
            return response()->json([
                'success' => false,
                'error' => 'No se encontró la convocatoria',
                'busqueda_utilizada' => $busqueda,
                'convocatorias_disponibles' => $disponibles,
                'sugerencia' => 'Usa ?buscar=nombre o ?id=123',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ], 404, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        // Ejecutar las mismas pruebas que el comando
        $fechaAjustada = \App\Helpers\ConvocatoriaHelper::ajustarFechaCierre($convocatoria->fechaCierre);
        $estaAbierta = \App\Helpers\ConvocatoriaHelper::convocatoriaEstaAbierta($convocatoria->fechaCierre);
        $enEntrevistas = \App\Helpers\ConvocatoriaHelper::convocatoriaEnEntrevistas($convocatoria->fechaCierre, $convocatoria->fechaEntrevistas);
        $convocatoriaActiva = \App\Helpers\ConvocatoriaHelper::obtenerConvocatoriaActiva();
        
        $response = [
            'success' => true,
            'convocatoria' => [
                'id' => $convocatoria->id,
                'nombre' => $convocatoria->nombre,
                'fecha_bd' => $convocatoria->fechaCierre,
                'fecha_entrevistas' => $convocatoria->fechaEntrevistas,
            ],
            'hora_actual' => now()->format('Y-m-d H:i:s'),
            'pruebas_helper' => [
                'fecha_ajustada' => $fechaAjustada->format('Y-m-d H:i:s'),
                'esta_abierta' => $estaAbierta,
                'en_entrevistas' => $enEntrevistas,
                'se_obtiene_como_activa' => $convocatoriaActiva ? true : false,
            ],
            'estado_sistema' => $estaAbierta ? '🟢 CONVOCATORIA ABIERTA' : ($enEntrevistas ? '🟡 PERÍODO DE ENTREVISTAS' : '🔴 CONVOCATORIA FINALIZADA'),
            'funcionalidades' => [
                'se_pueden_postular_estudiantes' => $estaAbierta ? '✅' : '❌',
                'se_pueden_aprobar_postulados' => $estaAbierta ? '✅' : '❌',
                'se_muestran_monitorias_activas' => $estaAbierta ? '✅' : '❌',
                'se_pueden_gestionar_entrevistas' => $enEntrevistas ? '✅' : '❌',
            ],
            'timestamp' => now()->format('Y-m-d H:i:s')
        ];
        
        return response()->json($response, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Error ejecutando las pruebas: ' . $e->getMessage(),
            'timestamp' => now()->format('Y-m-d H:i:s')
        ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
})->name('test.sistema.convocatoria');

// Ruta temporal con vista HTML para probar el sistema (ELIMINAR DESPUÉS DE PRUEBAS)
Route::get('/probar-convocatoria-html', function () {
    $convocatoria = \App\Models\Convocatoria::where('nombre', 'like', '%2025-II%')->first();
    
    if (!$convocatoria) {
        return '<h1>Error: No se encontró la convocatoria</h1>';
    }
    
    $fechaAjustada = \App\Helpers\ConvocatoriaHelper::ajustarFechaCierre($convocatoria->fechaCierre);
    $estaAbierta = \App\Helpers\ConvocatoriaHelper::convocatoriaEstaAbierta($convocatoria->fechaCierre);
    $enEntrevistas = \App\Helpers\ConvocatoriaHelper::convocatoriaEnEntrevistas($convocatoria->fechaCierre, $convocatoria->fechaEntrevistas);
    $convocatoriaActiva = \App\Helpers\ConvocatoriaHelper::obtenerConvocatoriaActiva();
    
    $estadoSistema = $estaAbierta ? 'ABIERTA' : ($enEntrevistas ? 'ENTREVISTAS' : 'FINALIZADA');
    $colorEstado = $estaAbierta ? 'green' : ($enEntrevistas ? 'orange' : 'red');
    
    return view('probar-convocatoria', compact('convocatoria', 'fechaAjustada', 'estaAbierta', 'enEntrevistas', 'convocatoriaActiva', 'estadoSistema', 'colorEstado'));
})->name('probar.convocatoria.html');


///Módulo subsidio Alimenticio

/// Módulo subsidio Alimenticio

// Alias 100% retrocompatible para el LoginController (NO LO QUITES)
// La ruta 'subsidio.admin.dashboard' se define dentro del grupo /admin más abajo.

// Admin Bienestar
Route::middleware(['auth','checkrole:AdminBienestar'])
    ->get('/subsidio/admin', [SubsidioAlimenticioController::class, 'dashboard'])
    ->name('subsidio.admin.dashboard');
Route::middleware(['auth', 'checkrole:AdminBienestar'])->prefix('admin')->as('admin.')->group(function () {

    // Dashboard (ruta nueva en /admin/subsidio, con nombre prefijado “admin.”)
    Route::get('/subsidio', [\App\Http\Controllers\SubsidioAlimenticioController::class, 'dashboard'])
        ->name('subsidio.admin.dashboard');

    Route::get('/estudiantes', function () {
        return redirect()->route('admin.estudiantes.index');
    })->name('estudiantes');

    // Módulo Estudiantes (Subsidio)
    Route::get('/subsidio', [SubsidioAlimenticioController::class, 'dashboard'])
        ->name('subsidio.admin.dashboard');

    // Index de Estudiantes: ÚNICA ruta para /admin/estudiantes (SIN redirect)
    Route::get('/estudiantes', [AdminEstudiantesController::class, 'index'])
        ->name('estudiantes');

    // Subrutas de Estudiantes (no declares aquí otro GET '/')
    Route::prefix('estudiantes')->as('estudiantes.')->group(function () {
        Route::get('/{user}', [AdminEstudiantesController::class, 'show'])->name('show');
        Route::post('/{user}/observaciones', [AdminEstudiantesController::class, 'storeObservacion'])->name('observaciones.store');
        Route::delete('/{user}/observaciones/{observacion}', [AdminEstudiantesController::class, 'destroyObservacion'])->name('observaciones.destroy');
    });

    // CRUD de convocatorias de subsidio (resource)
    Route::resource('/convocatorias-subsidio', \App\Http\Controllers\ConvocatoriaSubsidioController::class)
        ->names('convocatorias-subsidio');

    // Alias: /admin/convocatorias -> listado del resource
    Route::get('/convocatorias', fn() => redirect()->route('admin.convocatorias-subsidio.index'))
        ->name('convocatorias');

    // Postulaciones por convocatoria (UN SOLO BLOQUE, nombres estables)
    Route::prefix('convocatorias-subsidio')->as('convocatorias-subsidio.')->group(function () {

        // Listado por convocatoria
        Route::get('/{convocatoria}/postulaciones', [\App\Http\Controllers\AdminPostulacionSubsidioController::class, 'index'])
            ->name('postulaciones.index');

        // Detalle
        Route::get('/postulaciones/{postulacion}', [\App\Http\Controllers\AdminPostulacionSubsidioController::class, 'show'])
            ->name('postulaciones.show');

        // Estado
        Route::post('/postulaciones/{postulacion}/estado', [\App\Http\Controllers\AdminPostulacionSubsidioController::class, 'updateEstado'])
            ->name('postulaciones.estado');

        // PDF
        Route::get('/postulaciones/{postulacion}/pdf', [\App\Http\Controllers\AdminPostulacionSubsidioController::class, 'download'])
            ->name('postulaciones.pdf');

        // Prioridad (auto)
        Route::post('/postulaciones/{postulacion}/recalcular-prioridad', [\App\Http\Controllers\AdminPostulacionSubsidioController::class, 'recalcularPrioridad'])
            ->name('postulaciones.recalcular');

        // Prioridad (manual)
        Route::post('/postulaciones/{postulacion}/prioridad-manual', [\App\Http\Controllers\AdminPostulacionSubsidioController::class, 'updatePrioridadManual'])
            ->name('postulaciones.prioridad-manual');
    });

});

Route::middleware(['auth','checkrole:Estudiante'])->group(function () {
    Route::get('/subsidio/convocatorias', [\App\Http\Controllers\EstudianteConvocatoriaController::class, 'index'])
        ->name('subsidio.convocatorias.index');

    Route::get('/subsidio/convocatorias/{convocatoria}/postular', [\App\Http\Controllers\PostulacionSubsidioController::class, 'create'])
        ->name('subsidio.postulacion.create');

    Route::post('/subsidio/convocatorias/{convocatoria}/postular', [\App\Http\Controllers\PostulacionSubsidioController::class, 'store'])
        ->name('subsidio.postulacion.store');

    Route::get('/subsidio/convocatorias/{convocatoria}/gracias', [\App\Http\Controllers\PostulacionSubsidioController::class, 'gracias'])
        ->name('subsidio.postulacion.gracias');

    Route::get('/subsidio/mis-postulaciones', [\App\Http\Controllers\EstudiantePostulacionController::class, 'index'])
        ->name('subsidio.postulaciones.index');

    Route::get('/subsidio/postulaciones/{postulacion}', [\App\Http\Controllers\EstudiantePostulacionController::class, 'show'])
        ->name('subsidio.postulaciones.show');

    Route::get('/subsidio/postulaciones/{postulacion}/pdf', [\App\Http\Controllers\EstudiantePostulacionController::class, 'download'])
        ->name('subsidio.postulaciones.pdf');
}); // <-- Asegúrate de que esta llave cierra correctamente el grupo y que no hay otra llave extra después de este bloque.