<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespuestaSubsidio extends Model
{
    protected $table = 'subsidio_respuestas';
    protected $fillable = [
        'postulacion_id','pregunta_id',
        'respuesta_texto','respuesta_numero','respuesta_fecha',
        'opcion_id','opcion_ids','respuesta_json'
    ];

    protected $casts = [
        'opcion_ids' => 'array',
        'respuesta_json' => 'array', // matriz (mapa fila_id => valor_columna)
    ];

    public function pregunta()
    {
        return $this->belongsTo(PreguntaSubsidio::class, 'pregunta_id');
    }

    public function opcion()
    {
        return $this->belongsTo(OpcionSubsidio::class, 'opcion_id');
    }
}