<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class InventarioConteo extends Model
{
    use PerteneceATienda;

    protected $table = 'inventario_conteos';

    protected $fillable = ['user_id', 'nombre', 'categoria_id', 'estado', 'aplicado_por', 'aplicado_at', 'resumen'];

    protected $casts = ['resumen' => 'array', 'aplicado_at' => 'datetime'];

    public function items()
    {
        return $this->hasMany(InventarioConteoItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aplicador()
    {
        return $this->belongsTo(User::class, 'aplicado_por');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function getAbiertoAttribute(): bool
    {
        return $this->estado === 'abierto';
    }
}
