<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallesEvento extends Model
{
    use HasFactory;

    protected $table = 'detallesevento';

    protected $fillable = [
        'evento',
        'transporte',
        'audio',
        'proyeccion',
        'internet',
        'otros',
        'diseñoPublicitario',
        'redes',
        'correo',
        'whatsapp',
        'personal_recibo',
        'seguridad',
        'bienvenida',
        'defensoria_civil',
        'certificacion',
        'cubrimiento_medios',
        'servicio_general',
        'otro_Recurso',
        'estudiantes', 
        'profesores',
        'administrativos', 
        'empresarios', 
        'comunidad_general', 
        'egresados', 
        'invitados_externos',
        
    ];

    // Relación con el modelo Evento
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento');
    }
}
