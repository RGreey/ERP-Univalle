<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CupoPatron extends Model
{
    protected $table = 'subsidio_patrones_asignacion';

    protected $fillable = [
        'convocatoria_id','postulacion_id','user_id','dias_iso','sede','max_semanal',
    ];

    protected $casts = [
        'dias_iso' => 'array',
    ];

    public function convocatoria()
    {
        return $this->belongsTo(ConvocatoriaSubsidio::class, 'convocatoria_id');
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