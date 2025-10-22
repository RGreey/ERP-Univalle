<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandbyOferta extends Model
{
    protected $table = 'subsidio_standby_ofertas';
    protected $fillable = [
        'batch_id','cupo_diario_id','user_id','estado','token','enviado_en','vence_en','aceptada_en','via',
    ];
    protected $casts = [
        'enviado_en' => 'datetime',
        'vence_en'   => 'datetime',
        'aceptada_en'=> 'datetime',
    ];

    public function cupo() { return $this->belongsTo(CupoDiario::class, 'cupo_diario_id'); }
    public function user() { return $this->belongsTo(User::class); }
}