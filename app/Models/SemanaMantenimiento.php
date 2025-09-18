<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SemanaMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'semanas_mantenimiento';

    protected $fillable = [
        'actividad_id',
        'anio',
        'mes',
        'semana',
        'ejecutado',
        'fecha_ejecucion',
        'observaciones'
    ];

    protected $casts = [
        'ejecutado' => 'boolean',
        'fecha_ejecucion' => 'date',
        'anio' => 'integer',
        'mes' => 'integer',
        'semana' => 'integer'
    ];

    // Relaciones
    public function actividad()
    {
        return $this->belongsTo(ActividadMantenimiento::class, 'actividad_id');
    }

    // Scopes
    public function scopePorAnio($query, $anio)
    {
        return $query->where('anio', $anio);
    }

    public function scopePorMes($query, $mes)
    {
        return $query->where('mes', $mes);
    }

    public function scopeEjecutadas($query)
    {
        return $query->where('ejecutado', true);
    }

    public function scopePendientes($query)
    {
        return $query->where('ejecutado', false);
    }

    // Métodos
    public function getMesNombreAttribute()
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return $meses[$this->mes] ?? 'Desconocido';
    }

    public function getSemanaDisplayAttribute()
    {
        return "Semana {$this->semana}";
    }

    public function getIdentificadorAttribute()
    {
        return "{$this->anio}-{$this->mes}-{$this->semana}";
    }

    public function marcarEjecutada($fechaEjecucion = null, $observaciones = null)
    {
        $this->update([
            'ejecutado' => true,
            'fecha_ejecucion' => $fechaEjecucion ?? now(),
            'observaciones' => $observaciones
        ]);
    }

    public function marcarPendiente()
    {
        $this->update([
            'ejecutado' => false,
            'fecha_ejecucion' => null,
            'observaciones' => null
        ]);
    }
}
