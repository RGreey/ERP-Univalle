<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Strike extends Model
{
    protected $table = 'subsidio_strikes';
    protected $fillable = ['user_id','tipo','fecha','observacion'];
    protected $casts = ['fecha'=>'date'];

    public function user() { return $this->belongsTo(User::class); }
}