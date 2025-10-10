<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CupoAsignacion extends Model
{
    protected $table = 'subsidio_cupo_asignaciones';
    protected $fillable = [
        'cupo_diario_id','postulacion_id','user_id','estado',
        'qr_token','asignado_en','consumido_en','cancelado_en', // legacy
        // nuevos
        'asistencia_estado',
        'cancelada_en','cancelada_por_user_id','cancelacion_origen','cancelacion_motivo',
        'reversion_en','reversion_por_user_id','reversion_motivo',
        'es_reemplazo','reemplaza_asignacion_id',
    ];

    protected $casts = [
        'asignado_en'  => 'datetime',
        'consumido_en' => 'datetime',
        'cancelado_en' => 'datetime',   // legacy (si existiera)
        'cancelada_en' => 'datetime',
        'reversion_en' => 'datetime',
    ];

    public function cupo()
    {
        return $this->belongsTo(CupoDiario::class, 'cupo_diario_id');
    }

    public function postulacion()
    {
        return $this->belongsTo(PostulacionSubsidio::class, 'postulacion_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Dentro de la clase CupoAsignacion
    public function getAsistenciaEstadoNormalizadoAttribute(): string
    {
        $e = $this->asistencia_estado ?: 'pendiente';
        return $e === 'no_show' ? 'inasistencia' : $e;
    }

}