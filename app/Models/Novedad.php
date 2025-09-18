<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Novedad extends Model
{
    use HasFactory;

    protected $table = 'novedades';

    protected $fillable = [
        'titulo',
        'descripcion',
        'tipo',
        'lugar_id',
        'ubicacion_detallada',
        'usuario_id',
        'estado_novedad',
        'fecha_solicitud',
        'fecha_finalizacion',
    ];

    public function lugar()
    {
        return $this->belongsTo(Lugar::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function evidencias()
    {
        return $this->hasMany(Evidencia::class);
    }
} 