<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CupoAsignacion extends Model
{
    protected $table = 'subsidio_cupo_asignaciones';
    protected $fillable = [
        'cupo_diario_id','postulacion_id','user_id','estado',
        'qr_token','asignado_en','consumido_en','cancelado_en'
    ];
    protected $casts = [
        'asignado_en' => 'datetime',
        'consumido_en'=> 'datetime',
        'cancelado_en'=> 'datetime',
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
}