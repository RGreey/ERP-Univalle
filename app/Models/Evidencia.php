<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evidencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'novedad_id',
        'archivo_url',
        'descripcion',
        'fecha_subida',
    ];

    public function novedad()
    {
        return $this->belongsTo(Novedad::class);
    }
} 