<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostulacionSubsidio extends Model
{
    protected $table = 'subsidio_postulaciones';
    protected $fillable = [
        'user_id','convocatoria_id','sede','estado','puntaje_total','posicion',
        'documento_pdf','prioridad_base','prioridad_final','prioridad_calculada_en'
    ];

    protected $casts = [
        'prioridad_calculada_en' => 'datetime',
    ];

    public function respuestas()
    {
        return $this->hasMany(RespuestaSubsidio::class, 'postulacion_id');
    }

    public function convocatoria()
    {
        return $this->belongsTo(ConvocatoriaSubsidio::class, 'convocatoria_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Helpers existentes que ya te compartí (opcional mantener)
    public function respuestaPorTipo(string $tipo): ?RespuestaSubsidio
    {
        $rels = $this->relationLoaded('respuestas') ? $this->respuestas : $this->respuestas()->with('pregunta')->get();
        return $rels->first(function($r) use ($tipo) { return optional($r->pregunta)->tipo === $tipo; });
    }

    public function getProgramaAttribute(): ?string
    {
        return optional($this->respuestaPorTipo('programa_db'))->respuesta_texto;
    }

    public function getCorreoAttribute(): ?string
    {
        return optional($this->respuestaPorTipo('email'))->respuesta_texto;
    }

    public function getTelefonoAttribute(): ?string
    {
        return optional($this->respuestaPorTipo('telefono'))->respuesta_texto;
    }

    public function getPreferenciasDiasAttribute(): array
    {
        $r = $this->respuestaPorTipo('matrix_single');
        return $r && is_array($r->respuesta_json) ? $r->respuesta_json : [];
    }
}