<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpcionSubsidio extends Model
{
    protected $table = 'subsidio_opciones';
    protected $fillable = ['pregunta_id','texto','peso','orden'];

    public function pregunta()
    {
        return $this->belongsTo(PreguntaSubsidio::class, 'pregunta_id');
    }
}