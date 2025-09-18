<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramaDependencia extends Model
{
    use HasFactory;

    protected $table = 'programadependencia';
    protected $primaryKey = 'id';

    public function eventos()
    {
        return $this->belongsToMany(Evento::class, 'evento_dependencia', 'programadependencia_id', 'evento_id');
    }
}

