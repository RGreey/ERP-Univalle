<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Restaurante extends Model
{
    protected $table = 'subsidio_restaurantes';
    protected $fillable = ['codigo','nombre'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'subsidio_restaurante_user', 'restaurante_id', 'user_id');
    }
}