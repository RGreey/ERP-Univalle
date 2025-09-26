<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'rol',
        'rol_solicitado',
        'email',
        'cedula',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Mutator para convertir el nombre a mayúsculas
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = mb_strtoupper($value ?? '', 'UTF-8');
    }
    public function dependencia()
    {
        return $this->belongsTo(ProgramaDependencia::class, 'dependencia_id');
    }

    public function monitor()
    {
        return $this->hasOne(Monitor::class, 'user');
    }

    public function monitors()
    {
        return $this->hasMany(Monitor::class, 'user');
    }

    public function monitoriasEncargadas()
    {
        return $this->hasMany(Monitoria::class, 'encargado');
    }

    /**
     * Check if the user has one of the specified roles.
     *
     * @param string|array $roles
     * @return bool
     */
    public function hasRole($roles)
    {
        // Si $roles es un arreglo, verificamos si el usuario tiene alguno de los roles proporcionados
        if (is_array($roles)) {
            // Verificamos si el usuario tiene al menos uno de los roles permitidos
            foreach ($roles as $role) {
                if ($role === 'Profesor' || $role === 'Administrativo' || $role === 'Estudiante' || $role === 'CooAdmin' || $roles === 'AuxAdmin' || $roles === 'AdminBienestar') {
                    if ($this->rol === $role) {
                        return true;
                    }
                }
            }
            return false;
        } 
        // Si $roles no es un arreglo, verificamos si el usuario tiene el único rol permitido
        else {
            return $this->rol === $roles && ($roles === 'Profesor' || $roles === 'Administrativo' || $roles === 'Estudiante' || $roles === 'CooAdmin' || $roles === 'AuxAdmin' || $roles === 'AdminBienestar');
        }
    }
}