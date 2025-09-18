<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaqueteEvidencia extends Model
{
    use HasFactory;

    protected $table = 'paquetes_evidencias';

    protected $fillable = [
        'sede', 'mes', 'anio', 'archivo_pdf', 'usuario_id', 'descripcion_general'
    ];

    protected $casts = [
        'mes' => 'integer',
        'anio' => 'integer',
    ];

    public function fotos()
    {
        return $this->hasMany(FotoEvidencia::class, 'paquete_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}


