<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreguntaSubsidio extends Model
{
    protected $table = 'subsidio_preguntas';
    protected $fillable = ['titulo','descripcion','tipo','obligatoria','grupo','orden_global'];

    public function opciones()
    {
        return $this->hasMany(OpcionSubsidio::class, 'pregunta_id')->orderBy('orden');
    }

    // Matriz
    public function filas()
    {
        return $this->hasMany(SubsidioMatrizFila::class, 'pregunta_id')->orderBy('orden');
    }

    public function columnas()
    {
        return $this->hasMany(SubsidioMatrizColumna::class, 'pregunta_id')->orderBy('orden');
    }
}