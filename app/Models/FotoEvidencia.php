<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FotoEvidencia extends Model
{
    use HasFactory;

    protected $table = 'fotos_evidencia';

    protected $fillable = [
        'paquete_id', 'actividad_id', 'archivo', 'descripcion', 'orden'
    ];

    public function paquete()
    {
        return $this->belongsTo(PaqueteEvidencia::class, 'paquete_id');
    }

    public function actividad()
    {
        return $this->belongsTo(ActividadMantenimiento::class, 'actividad_id');
    }
}


