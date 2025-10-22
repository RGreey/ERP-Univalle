<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandbyRegistro extends Model
{
    protected $table = 'subsidio_standby_registros';
    protected $fillable = [
        'convocatoria_id','user_id','es_externo','activo',
        'pref_lun','pref_mar','pref_mie','pref_jue','pref_vie',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function convocatoria() { return $this->belongsTo(ConvocatoriaSubsidio::class, 'convocatoria_id'); }
}