<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsistenciaMonitoria extends Model
{
    use HasFactory;
    
    protected $table = 'asistencias_monitoria';

    protected $fillable = [
        'monitor_id', 'mes', 'anio', 'asistencia_path'
    ];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class, 'monitor_id');
    }
}
