<?php

namespace App\Http\Controllers;

use App\Models\ConvocatoriaSubsidio;
use App\Models\PostulacionSubsidio;
use App\Models\RespuestaSubsidio;
use App\Models\ProgramaDependencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PostulacionSubsidioController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','checkrole:Estudiante']);
    }

    public function create(ConvocatoriaSubsidio $convocatoria)
    {
        abort_unless($convocatoria->estado_actual === 'activa', 403, 'Convocatoria no activa');
        abort_unless($convocatoria->encuesta_id && $convocatoria->recepcion_habilitada, 403, 'Postulación no disponible');

        // Si ya existe una postulación del usuario para esta convocatoria, redirigir a su detalle
        $existing = PostulacionSubsidio::where('user_id', auth()->id())
            ->where('convocatoria_id', $convocatoria->id)
            ->first();

        if ($existing) {
            return redirect()
                ->route('subsidio.postulaciones.show', $existing->id)
                ->with('info', 'Ya tienes una postulación registrada para esta convocatoria.');
        }

        $convocatoria->load(['encuesta.preguntas.opciones','encuesta.preguntas.filas','encuesta.preguntas.columnas']);

        // Programas (columna real es nombrePD)
        $programas = ProgramaDependencia::orderBy('nombrePD')->get(['id', \DB::raw('nombrePD as nombre')]);

        return view('roles.estudiante.subsidio.postulacion', [
            'convocatoria' => $convocatoria,
            'encuesta'     => $convocatoria->encuesta,
            'programas'    => $programas,
        ]);
    }


    public function store(Request $request, ConvocatoriaSubsidio $convocatoria)
    {
        abort_unless($convocatoria->estado_actual === 'activa', 403, 'Convocatoria no activa');
        abort_unless($convocatoria->encuesta_id && $convocatoria->recepcion_habilitada, 403, 'Postulación no disponible');

        // Validación base
        $request->validate([
            'sede'          => ['required','in:Caicedonia,Sevilla'],
            'respuestas'    => ['required','array'],
            'documento_pdf' => ['required','file','mimes:pdf','max:10240'],
        ],[
            'documento_pdf.required' => 'Debes adjuntar el PDF único con la documentación.',
            'documento_pdf.mimes'    => 'El archivo debe ser PDF.',
            'documento_pdf.max'      => 'El PDF no debe superar 10MB.',
        ]);

        $userId = auth()->id();

        DB::transaction(function () use ($convocatoria, $userId, $request) {
            if (PostulacionSubsidio::where('user_id',$userId)->where('convocatoria_id',$convocatoria->id)->exists()) {
                throw ValidationException::withMessages([
                    'general' => 'Ya enviaste una postulación para esta convocatoria.',
                ]);
            }

            $path = $request->file('documento_pdf')->store(
                'subsidio/postulaciones/'.$convocatoria->id.'/'.$userId,
                'public'
            );

            $postulacion = PostulacionSubsidio::create([
                'user_id'       => $userId,
                'convocatoria_id'=> $convocatoria->id,
                'sede'          => $request->string('sede'),
                'estado'        => 'enviada',
                'documento_pdf' => $path,
            ]);

            // Validación por pregunta (amigable)
            $convocatoria->load(['encuesta.preguntas.opciones','encuesta.preguntas.filas','encuesta.preguntas.columnas']);
            $mapPreg = $convocatoria->encuesta->preguntas->keyBy('id');
            $input   = $request->input('respuestas', []);
            $errors  = [];

            foreach ($mapPreg as $preguntaId => $preg) {
                if ($preg->tipo === 'parrafo') continue;

                $payload = $input[$preguntaId] ?? null;
                if ($preg->pivot->obligatoria && (is_null($payload) || $payload === '' || $payload === [])) {
                    $errors["respuestas.$preguntaId"] = "La pregunta '{$preg->titulo}' es obligatoria.";
                    continue;
                }

                $toCreate = ['postulacion_id' => $postulacion->id, 'pregunta_id' => $preguntaId];

                switch ($preg->tipo) {
                    case 'seleccion_unica':
                    case 'boolean':
                        $opId = $payload['opcion_id'] ?? null;
                        if ($preg->pivot->obligatoria && !$opId) {
                            $errors["respuestas.$preguntaId"] = "Selecciona una opción en '{$preg->titulo}'.";
                            break;
                        }
                        $toCreate['opcion_id'] = $opId;
                        break;

                    case 'seleccion_multiple':
                        $ids = $payload['opcion_ids'] ?? [];
                        if ($preg->pivot->obligatoria && count($ids) === 0) {
                            $errors["respuestas.$preguntaId"] = "Selecciona al menos una opción en '{$preg->titulo}'.";
                            break;
                        }
                        $toCreate['opcion_ids'] = $ids;
                        break;

                    case 'texto':
                    case 'email':
                    case 'telefono':
                        $txt = $payload['texto'] ?? null;
                        if ($preg->pivot->obligatoria && ($txt === null || trim($txt) === '')) {
                            $errors["respuestas.$preguntaId"] = "Completa el campo '{$preg->titulo}'.";
                            break;
                        }
                        $toCreate['respuesta_texto'] = $txt;
                        break;

                    case 'numero':
                        $num = $payload['numero'] ?? null;
                        if ($preg->pivot->obligatoria && ($num === null || $num === '')) {
                            $errors["respuestas.$preguntaId"] = "Completa el campo '{$preg->titulo}'.";
                            break;
                        }
                        $toCreate['respuesta_numero'] = $num;
                        break;

                    case 'fecha':
                        $fec = $payload['fecha'] ?? null;
                        if ($preg->pivot->obligatoria && ($fec === null || $fec === '')) {
                            $errors["respuestas.$preguntaId"] = "Selecciona una fecha en '{$preg->titulo}'.";
                            break;
                        }
                        $toCreate['respuesta_fecha'] = $fec;
                        break;

                    case 'matrix_single':
                        $map = is_array($payload) ? $payload : [];
                        foreach ($preg->filas as $f) {
                            if (!array_key_exists($f->id, $map)) {
                                $errors["respuestas.$preguntaId.$f->id"] = "Selecciona una opción para '{$f->etiqueta}'.";
                            } else {
                                $allowed = $preg->columnas->pluck('valor')->all();
                                if (!in_array($map[$f->id], $allowed, true)) {
                                    $errors["respuestas.$preguntaId.$f->id"] = "Valor inválido en '{$preg->titulo}' para '{$f->etiqueta}'.";
                                }
                            }
                        }
                        $toCreate['respuesta_json'] = $map;
                        break;

                    case 'programa_db':
                        $progId = $payload['programa_id'] ?? null;
                        if ($preg->pivot->obligatoria && !$progId) {
                            $errors["respuestas.$preguntaId"] = "Selecciona tu programa.";
                            break;
                        }
                        if ($progId) {
                            $prog = ProgramaDependencia::find($progId);
                            if (!$prog) {
                                $errors["respuestas.$preguntaId"] = "Programa inválido.";
                                break;
                            }
                            // Guardamos el nombre real de BD (columna nombrePD)
                            $toCreate['respuesta_texto'] = $prog->nombrePD;
                        }
                        break;
                }

                if (count($toCreate) > 2 && empty($errors["respuestas.$preguntaId"])) {
                    RespuestaSubsidio::create($toCreate);
                }
            }

            if (!empty($errors)) {
                throw ValidationException::withMessages($errors);
            }
        });
        
        return redirect()->route('subsidio.postulacion.gracias', $convocatoria->id)
            ->with('success', '¡Tu postulación fue enviada con éxito!');
    }
    public function gracias(ConvocatoriaSubsidio $convocatoria)
{
    // Solo permite ver la página si el usuario tiene una postulación para esta convocatoria
    $tiene = PostulacionSubsidio::where('convocatoria_id', $convocatoria->id)
        ->where('user_id', auth()->id())
        ->exists();

    abort_unless($tiene, 403);

    // Muestra la vista simple de agradecimiento (ya existe en resources/views/roles/estudiante/subsidio/gracias.blade.php)
    return view('roles.estudiante.subsidio.gracias');
}
}