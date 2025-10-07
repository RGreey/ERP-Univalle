<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporteSubsidio extends Model
{
    protected $table = 'subsidio_reportes';

    protected $fillable = [
        'user_id',
        'tipo',
        'titulo',
        'descripcion',
        'sede',
        'origen',
        'estado',
        'admin_respuesta',
        'respondido_en',
    ];

    protected $casts = [
        'respondido_en' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}