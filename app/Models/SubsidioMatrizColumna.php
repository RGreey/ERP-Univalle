<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubsidioMatrizColumna extends Model
{
    protected $table = 'subsidio_matriz_columnas';
    protected $fillable = ['pregunta_id','etiqueta','valor','orden'];

    public function pregunta()
    {
        return $this->belongsTo(PreguntaSubsidio::class, 'pregunta_id');
    }
}