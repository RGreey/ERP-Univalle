<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Espacio extends Model
{   
    protected $table = 'espacio';
    protected $fillable = ['nombreEspacio', 'lugar']; // Asegúrate de incluir los campos que deseas que se puedan llenar masivamente.

    // Relación con Lugar
    public function lugar()
    {
        return $this->belongsTo(Lugar::class, 'lugar');
    }
}


