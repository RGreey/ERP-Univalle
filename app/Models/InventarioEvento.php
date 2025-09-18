<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioEvento extends Model
{
    use HasFactory;

    protected $table = 'inventarioevento';

    protected $fillable = [
        'evento',
        'tipo',
        'cantidad',
    ];

    // Relación con el modelo Evento
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento');
    } 
}
