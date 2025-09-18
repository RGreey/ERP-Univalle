<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Monitoria extends Model
{
    use HasFactory;
    protected $table = 'monitorias';

    protected $fillable = [
        'nombre',
        'convocatoria',
        'programadependencia',
        'vacante',
        'intensidad',
        'horario',
        'requisitos',
        'modalidad',
        'estado',
        'encargado'
    ];

    // Mutator para estandarizar el nombre en mayúsculas al guardar
    public function setNombreAttribute($value)
    {
        $this->attributes['nombre'] = mb_strtoupper($value ?? '', 'UTF-8');
    }

    /**
     * Get the convocatoria associated with the monitoria.
     */
    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class);
    }

    /**
     * Get the programadependencia associated with the monitoria.
     */
    public function programadependencia()
    {
        return $this->belongsTo(ProgramaDependencia::class, 'programadependencia');
    }

    /**
     * Get the monitor associated with the monitoria.
     */
    public function monitor()
    {
        return $this->hasOne(Monitor::class, 'monitoria'); // Mantener para compatibilidad
    }

    /**
     * Get all monitors associated with the monitoria.
     */
    public function monitors()
    {
        return $this->hasMany(Monitor::class, 'monitoria');
    }

    /**
     * Get the user who created the monitoria.
     */
    public function encargado()
    {
        return $this->belongsTo(User::class, 'encargado');
    }
}
