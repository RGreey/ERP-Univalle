<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubsidioObservacion extends Model
{
    protected $table = 'subsidio_observaciones';

    protected $fillable = [
        'user_id', 'admin_id', 'texto',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}