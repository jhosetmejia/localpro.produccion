<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Abono (pago parcial) de un alquiler. El dinero entra a la caja donde se recibió.
class AlquilerAbono extends Model
{
    use PerteneceATienda;

    protected $table = 'alquiler_abonos';

    protected $fillable = ['alquiler_id', 'numero', 'caja_id', 'user_id', 'metodo_pago_id', 'monto', 'saldo_despues', 'nota', 'anulado'];

    protected $casts = ['monto' => 'float', 'saldo_despues' => 'float', 'anulado' => 'boolean'];

    public function alquiler() { return $this->belongsTo(Alquiler::class); }
    public function metodo() { return $this->belongsTo(MetodoPago::class, 'metodo_pago_id'); }
    public function user() { return $this->belongsTo(User::class); }

    public static function siguienteNumero(): string
    {
        $ultimo = (int) substr((string) static::query()->orderByDesc('id')->value('numero'), 4);
        return 'ABA-' . str_pad((string) ($ultimo + 1), 6, '0', STR_PAD_LEFT);
    }
}
