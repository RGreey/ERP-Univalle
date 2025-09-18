<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'actividades_mantenimiento';

    protected $fillable = [
        'actividad',
        'descripcion',
        'frecuencia',
        'fecha_inicio',
        'fecha_final',
        'realizado',
        'proveedor',
        'responsable',
        'orden'
    ];

    protected $casts = [
        'realizado' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_final' => 'date',
        'orden' => 'integer'
    ];

    // Relaciones
    public function semanas()
    {
        return $this->hasMany(SemanaMantenimiento::class, 'actividad_id');
    }

    // Scopes
    public function scopeRealizadas($query)
    {
        return $query->where('realizado', true);
    }

    public function scopePendientes($query)
    {
        return $query->where('realizado', false);
    }

    public function scopePorFrecuencia($query, $frecuencia)
    {
        return $query->where('frecuencia', $frecuencia);
    }

    public function scopePorProveedor($query, $proveedor)
    {
        if ($proveedor === 'servicios_generales') {
            return $query->whereNull('proveedor');
        }
        return $query->where('proveedor', $proveedor);
    }

    // Métodos
    public function getProveedorDisplayAttribute()
    {
        return $this->proveedor ?? 'Servicios Generales';
    }

    public function getEstadoAttribute()
    {
        return $this->realizado ? 'Realizada' : 'Pendiente';
    }

    public function getEstadoClassAttribute()
    {
        return $this->realizado ? 'success' : 'warning';
    }

    public function getRangoFechasAttribute()
    {
        if ($this->fecha_inicio->equalTo($this->fecha_final)) {
            return $this->fecha_inicio->format('d/m/Y');
        }
        return $this->fecha_inicio->format('d/m/Y') . ' - ' . $this->fecha_final->format('d/m/Y');
    }

    // Generar semanas para un año específico
    public function generarSemanas($anio)
    {
        // Eliminar semanas existentes del año
        $this->semanas()->where('anio', $anio)->delete();
        
        $semanas = [];
        
        // Generar 48 semanas (12 meses x 4 semanas)
        for ($mes = 1; $mes <= 12; $mes++) {
            for ($semana = 1; $semana <= 4; $semana++) {
                $semanas[] = [
                    'actividad_id' => $this->id,
                    'anio' => $anio,
                    'mes' => $mes,
                    'semana' => $semana,
                    'ejecutado' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        SemanaMantenimiento::insert($semanas);
    }
}
