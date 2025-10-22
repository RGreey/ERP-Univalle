<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BajaSubsidio extends Model
{
    protected $table = 'subsidio_bajas';
    protected $fillable = ['convocatoria_id','user_id','motivo','detalle','evidencia_pdf_path','admin_user_id'];

    public function user() { return $this->belongsTo(User::class); }
    public function admin() { return $this->belongsTo(User::class, 'admin_user_id'); }
    public function convocatoria() { return $this->belongsTo(ConvocatoriaSubsidio::class, 'convocatoria_id'); }
}