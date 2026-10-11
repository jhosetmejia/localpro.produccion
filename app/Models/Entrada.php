<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Entrada de mercancía (lo que llega del proveedor)
class Entrada extends Model
{
    use PerteneceATienda;

    protected $table = 'entradas';

    protected $fillable = ['proveedore_id', 'user_id', 'fecha', 'numero_factura', 'total', 'nota'];

    protected $casts = ['fecha' => 'date'];

    public function items()
    {
        return $this->hasMany(EntradaItem::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedore::class, 'proveedore_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
