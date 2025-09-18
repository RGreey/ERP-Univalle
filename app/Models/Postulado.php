<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Postulado extends Model
{
    use HasFactory;
    protected $table = 'postulados';
    protected $fillable = [
        'user',
        'convocatoria',
        'monitoria', 
        'estado',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user', 'id');
    }


    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class);
    }

    public function monitoria()
    {
        return $this->belongsTo(Monitoria::class, 'monitoria');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'postulado', 'id');
    }

    public function monitor()
    {
        return $this->hasOne(Monitor::class, 'user', 'user');
    }
}
