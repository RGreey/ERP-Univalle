<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubsidioMatrizFila extends Model
{
    protected $table = 'subsidio_matriz_filas';
    protected $fillable = ['pregunta_id','etiqueta','orden'];

    public function pregunta()
    {
        return $this->belongsTo(PreguntaSubsidio::class, 'pregunta_id');
    }
}