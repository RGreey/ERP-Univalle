<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConvocatoriaSubsidio extends Model
{
    use HasFactory;

    protected $table = 'convocatorias_subsidio';

    // IMPORTANTE: ya no recibimos 'estado' desde formularios; lo calculamos.
    protected $fillable = [
        'nombre',
        'periodo_academico',
        'fecha_apertura',
        'fecha_cierre',
        'cupos_caicedonia',
        'cupos_sevilla',
        // 'estado' intencionalmente fuera de fillable
    ];

    // Para poder usar $c->estado_actual en las vistas
    protected $appends = ['estado_actual'];

    protected static function booted()
    {
        // Asegurar coherencia: cada vez que se guarde, se recalcula estado
        static::saving(function (self $model) {
            $model->estado = $model->computeEstado();
        });
    }

    // Relación
    public function periodoAcademico()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_academico');
    }

    // Lógica de estado
    public function computeEstado(): string
    {
        $hoy = now()->toDateString();

        if ($this->fecha_apertura > $hoy) return 'borrador';
        if ($this->fecha_cierre < $hoy)  return 'cerrada';
        return 'activa';
    }

    // Accessor para usar en Blade: $modelo->estado_actual
    public function getEstadoActualAttribute(): string
    {
        return $this->computeEstado();
    }

    // Scope para filtrar por el estado calculado (no por la columna)
    public function scopeEstadoActual($query, ?string $estado)
    {
        if (!$estado) return $query;

        $hoy = now()->toDateString();

        return match ($estado) {
            'borrador' => $query->whereDate('fecha_apertura', '>', $hoy),
            'activa'   => $query->whereDate('fecha_apertura', '<=', $hoy)
                                ->whereDate('fecha_cierre', '>=', $hoy),
            'cerrada'  => $query->whereDate('fecha_cierre', '<', $hoy),
            default    => $query,
        };
    }
}