<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CupoDiario extends Model
{
    protected $table = 'subsidio_cupos_diarios';
    protected $fillable = ['convocatoria_id','fecha','sede','capacidad','asignados'];
    protected $casts = ['fecha' => 'date'];

    public function convocatoria()
    {
        return $this->belongsTo(ConvocatoriaSubsidio::class, 'convocatoria_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(CupoAsignacion::class, 'cupo_diario_id');
    }
    public function ocupacionActiva(): int
    {
        return $this->asignaciones()
            ->where(function ($q) {
                $q->whereNull('asistencia_estado')->orWhere('asistencia_estado', '!=', 'cancelado');
            })
            ->count();
    }

    // Vacantes basadas en ocupación activa
    public function vacantesActivas(): int
    {
        return max(0, (int) $this->capacidad - (int) $this->ocupacionActiva());
    }
}