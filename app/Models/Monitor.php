<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Monitor extends Model
{
    use HasFactory;
    protected $table = 'monitor';
    protected $fillable = [
        'user',
        'monitoria',
        'fecha_vinculacion',
        'fecha_culminacion',
        'horas_mensuales',
        'estado',
    ];

    protected $casts = [
        'horas_mensuales' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function monitoria()
    {
        return $this->belongsTo(Monitoria::class, 'monitoria');
    }
    
    public function seguimientos()
    {
        return $this->hasMany(Seguimiento::class);
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoMonitor::class, 'monitor_id');
    }
}

