<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Convocatoria extends Model
{
    use HasFactory;

    protected $table = 'convocatorias';

    protected $fillable = [
        'nombre',
        'periodoAcademico',
        'fechaApertura',
        'fechaCierre',
        'fechaEntrevistas',
        'horas_administrativo',
        'horas_docencia',
        'horas_investigacion'
    ];

    protected $casts = [
        'fechaApertura' => 'datetime',
        'fechaCierre' => 'datetime',
        'fechaEntrevistas' => 'datetime'
    ];

    public function periodoAcademico()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodoAcademico');
    }

    public function isActive()
    {
        return Carbon::now()->isBefore($this->fechaCierre);
    }
}
