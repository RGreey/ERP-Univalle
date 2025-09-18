<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoMonitor extends Model
{
    use HasFactory;

    protected $table = 'documentos_monitor';

    protected $fillable = [
        'monitor_id',
        'tipo_documento',
        'mes',
        'anio',
        'ruta_archivo',
        'parametros_generacion',
        'estado',
        'fecha_generacion'
    ];

    protected $casts = [
        'parametros_generacion' => 'array',
        'fecha_generacion' => 'datetime'
    ];

    /**
     * Relación con el monitor
     */
    public function monitor()
    {
        return $this->belongsTo(Monitor::class, 'monitor_id');
    }

    /**
     * Obtener el nombre del mes en español
     */
    public function getNombreMesAttribute()
    {
        if (!$this->mes) return null;
        
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return $meses[$this->mes] ?? '';
    }

    /**
     * Obtener el nombre del tipo de documento
     */
    public function getNombreTipoAttribute()
    {
        $tipos = [
            'seguimiento' => 'Seguimiento Mensual',
            'asistencia' => 'Asistencia Mensual',
            'evaluacion_desempeno' => 'Evaluación de Desempeño'
        ];
        
        return $tipos[$this->tipo_documento] ?? $this->tipo_documento;
    }

    /**
     * Obtener el icono del tipo de documento
     */
    public function getIconoTipoAttribute()
    {
        $iconos = [
            'seguimiento' => 'fa-solid fa-eye',
            'asistencia' => 'fa-solid fa-file-pdf',
            'evaluacion_desempeno' => 'fa-solid fa-file-pdf'
        ];
        
        return $iconos[$this->tipo_documento] ?? 'fa-solid fa-file';
    }

    /**
     * Obtener la clase CSS del estado
     */
    public function getClaseEstadoAttribute()
    {
        $clases = [
            'generado' => 'btn-outline-secondary',
            'firmado' => 'btn-outline-success',
            'pendiente' => 'btn-outline-warning'
        ];
        
        return $clases[$this->estado] ?? 'btn-outline-secondary';
    }

    /**
     * Verificar si el documento existe físicamente
     */
    public function existeArchivo()
    {
        if ($this->ruta_archivo) {
            return \Storage::disk('public')->exists($this->ruta_archivo);
        }
        return true; // Para PDFs generados dinámicamente
    }

    /**
     * Obtener la URL del documento
     */
    public function getUrlAttribute()
    {
        switch ($this->tipo_documento) {
            case 'seguimiento':
                return route('monitoria.seguimiento.pdf', [
                    'monitor_id' => $this->monitor_id,
                    'mes' => $this->mes
                ]);
            case 'asistencia':
                return route('monitoria.asistencia.ver', [
                    'monitor_id' => $this->monitor_id,
                    'anio' => $this->anio,
                    'mes' => $this->mes
                ]);
            case 'evaluacion_desempeno':
                return route('monitoria.desempeno.pdf', [
                    'monitor_id' => $this->monitor_id
                ]);
            default:
                return '#';
        }
    }
}
