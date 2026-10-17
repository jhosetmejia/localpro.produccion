<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use PerteneceATienda;

    use HasFactory;

    public function persona(){
        return $this->belongsTo(Persona::class);
    }

    public function ventas(){
        return $this->hasMany(Venta::class);
    }

    // Quién del equipo le escribió (Contactar clientes nuevos)
    public function contactadoPor(){
        return $this->belongsTo(User::class, 'contactado_por');
    }

    protected $fillable = ['persona_id'];

    protected $casts = ['contactado_at' => 'datetime', 'asignado_fecha' => 'date'];
}
