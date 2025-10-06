<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ConvocatoriaSubsidio extends Model
{
    use HasFactory;

    protected $table = 'convocatorias_subsidio';

    // No recibimos 'estado' por request; se calcula.
    protected $fillable = [
        'nombre',
        'periodo_academico',
        'fecha_apertura',
        'fecha_cierre',
        'cupos_caicedonia',
        'cupos_sevilla',
        'encuesta_id',
        'recepcion_habilitada',
        'fecha_inicio_beneficio',
        'fecha_fin_beneficio',
    ];

    protected $casts = [
        'fecha_apertura'       => 'date',
        'fecha_cierre'         => 'date',
        'fecha_inicio_beneficio'  => 'date',
        'fecha_fin_beneficio'     => 'date',
        'recepcion_habilitada' => 'bool',
    ];

    // Para usar $modelo->estado_actual en Blade
    protected $appends = ['estado_actual'];

    protected static function booted()
    {
        // Mantener 'estado' coherente cuando cambien fechas
        static::saving(function (self $model) {
            $model->estado = $model->computeEstado();
        });
    }

    /*
    |-------------------------
    | Relaciones
    |-------------------------
    */
    public function periodoAcademico()
    {
        // FK: periodo_academico -> tabla periodoAcademico (según tu modelo existente)
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_academico');
    }

    public function encuesta()
    {
        // Requiere tabla subsidio_encuestas si ya migraste
        return $this->belongsTo(EncuestaSubsidio::class, 'encuesta_id');
    }

    public function postulaciones()
    {
        // Requiere tabla subsidio_postulaciones si ya migraste
        return $this->hasMany(PostulacionSubsidio::class, 'convocatoria_id');
    }

    public function beneficiarios()
    {
        // Requiere tabla beneficiarios_subsidio si la agregas
        return $this->hasMany(BeneficiarioSubsidio::class, 'convocatoria_id');
    }

    /*
    |-------------------------
    | Lógica de estado
    |-------------------------
    */
    public function computeEstado(): string
    {
        // Con casts de fecha, ya son Carbon|nullable
        $apertura = $this->fecha_apertura instanceof Carbon ? $this->fecha_apertura : Carbon::parse($this->fecha_apertura);
        $cierre   = $this->fecha_cierre   instanceof Carbon ? $this->fecha_cierre   : Carbon::parse($this->fecha_cierre);
        $hoy = Carbon::today();

        if ($apertura->isFuture()) return 'borrador';
        if ($cierre->isPast())     return 'cerrada';
        return 'activa';
    }

    // Accessor: $modelo->estado_actual
    public function getEstadoActualAttribute(): string
    {
        return $this->computeEstado();
    }

    /*
    |-------------------------
    | Scopes y helpers
    |-------------------------
    */
    // Filtrar por estado calculado (no por columna)
    public function scopeEstadoActual($query, ?string $estado)
    {
        if (!$estado) return $query;

        $hoy = Carbon::today()->toDateString();

        return match ($estado) {
            'borrador' => $query->whereDate('fecha_apertura', '>', $hoy),
            'activa'   => $query->whereDate('fecha_apertura', '<=', $hoy)
                                ->whereDate('fecha_cierre', '>=', $hoy),
            'cerrada'  => $query->whereDate('fecha_cierre', '<', $hoy),
            default    => $query,
        };
    }

    // Lista “abiertas para postulación” (convocatoria activa + encuesta asignada + recepción ON)
    public function scopeAbiertasParaPostulacion($query)
    {
        $hoy = Carbon::today()->toDateString();
        return $query->whereNotNull('encuesta_id')
            ->where('recepcion_habilitada', true)
            ->whereDate('fecha_apertura', '<=', $hoy)
            ->whereDate('fecha_cierre', '>=', $hoy);
    }

    // Helper OO para vistas/controladores
    public function estaAbiertaParaPostular(): bool
    {
        return $this->estado_actual === 'activa'
            && !empty($this->encuesta_id)
            && (bool)$this->recepcion_habilitada;
    }
}