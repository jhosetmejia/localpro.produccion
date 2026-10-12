<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    use PerteneceATienda;

    protected $table = 'metodos_pago';

    protected $fillable = ['nombre', 'es_efectivo', 'es_credito', 'activo', 'interno', 'orden'];

    protected $casts = ['es_efectivo' => 'boolean', 'es_credito' => 'boolean', 'activo' => 'boolean'];

    // Método interno para pagar con saldo a favor de un cambio (se crea si no existe)
    public static function saldoAFavor(): self
    {
        return static::firstOrCreate(['nombre' => 'Saldo a favor'], ['es_efectivo' => false, 'es_credito' => true, 'activo' => true, 'orden' => 99]);
    }

    // Método interno para entregar un separado ya pagado con abonos (no suma dinero a la caja)
    public static function pagadoConAbonos(): self
    {
        return static::firstOrCreate(['nombre' => 'Pagado con abonos'], ['es_efectivo' => false, 'es_credito' => true, 'activo' => false, 'interno' => true, 'orden' => 100]);
    }

    public function scopeActivos($q)
    {
        return $q->where('activo', true)->where('es_credito', false)->orderBy('orden')->orderBy('nombre');
    }
}
