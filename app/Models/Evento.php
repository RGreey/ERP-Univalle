<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use HasFactory;

    protected $table = 'evento';

    public function dependencias()
    {
        return $this->belongsToMany(ProgramaDependencia::class, 'evento_dependencia', 'evento_id', 'programadependencia_id');
    }

    // Relación con Lugar
    public function lugar()
    {
        return $this->belongsTo(Lugar::class, 'lugar');
    }

    // Relación con Espacio
    public function espacio()
    {
        return $this->belongsTo(Espacio::class, 'espacio');
    }

    // Relación con Usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function detallesEvento()
    {
        return $this->hasOne(DetallesEvento::class, 'evento');
    }
    
    // Relación uno a muchos con InventarioEvento
    public function inventarioEventos()
    {
        return $this->hasMany(InventarioEvento::class);
    }

    // Relación con calificacion.
    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }
}

