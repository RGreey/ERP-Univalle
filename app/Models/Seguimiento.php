<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seguimiento extends Model
{
    use HasFactory;
    protected $table = 'seguimiento';
    protected $fillable = [
        'monitor', 'fecha_monitoria', 'hora_ingreso', 'hora_salida', 'total_horas', 'actividad_realizada',
        'firma_digital', 'firma_size', 'firma_pos'
    ];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
