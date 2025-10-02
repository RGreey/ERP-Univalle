<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuestaSubsidio extends Model
{
    protected $table = 'subsidio_encuestas';
    protected $fillable = ['nombre','version','estado'];

    public function preguntas()
    {
        return $this->belongsToMany(PreguntaSubsidio::class, 'subsidio_encuesta_pregunta', 'encuesta_id', 'pregunta_id')
            ->withPivot(['orden','obligatoria','peso_override'])
            ->orderBy('subsidio_encuesta_pregunta.orden');
    }
}