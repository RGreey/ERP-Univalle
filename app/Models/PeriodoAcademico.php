<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodoAcademico extends Model
{
    use HasFactory;
    protected $table = 'periodoAcademico';

    protected $fillable = ['nombre', 'tipo', 'fechaInicio', 'fechaFin'];

    public function convocatoria()
    {
        return $this->hasOne(Convocatoria::class, 'periodoAcademico');
    }
}
