<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesempenoMonitor extends Model
{
    protected $table = 'desempeno_monitor';

    protected $fillable = [
        'monitor_id',
        'convocatoria_id',
        'periodo_academico',
        'codigo_estudiantil',
        'programa_academico',
        'apellidos_estudiante',
        'nombres_estudiante',
        'modalidad_monitoria',
        'dependencia',
        'evaluador_identificacion',
        'evaluador_apellidos',
        'evaluador_nombres',
        'evaluador_cargo',
        'evaluador_dependencia',
        'fecha_inicio',
        'fecha_fin',
        'calidad_trabajo',
        'sigue_instrucciones',
        'responsable_actividad',
        'iniciativa',
        'cumplimiento_horario',
        'relaciones_interpersonales',
        'cooperacion',
        'atencion_usuario',
        'asume_compromisos',
        'maneja_informacion',
        'puntaje_total',
        'sugerencias',
        'fecha_evaluacion',
        'firma_evaluador',
        'firma_evaluado',
    ];

    // Relaciones
    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class);
    }
}
