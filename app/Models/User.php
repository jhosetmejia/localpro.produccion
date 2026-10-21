<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'telefono',
        'password',
        'tienda_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    // Rol propio del negocio (Caja, Mesero, Cocina…)
    public function negocioRol()
    {
        return $this->belongsTo(NegocioRol::class, 'negocio_rol_id');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class);
    }

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
        'es_superadmin' => 'boolean',
        'terminos_aceptados_at' => 'datetime',
    ];

    public function ventas(){
        return $this->hasMany(Venta::class);
    }

    // Si pertenece a la red comercial (asesor o líder)
    public function asesor()
    {
        return $this->hasOne(Asesor::class);
    }
}
