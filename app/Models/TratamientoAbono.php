<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Abono a un plan de tratamiento: entra a la caja abierta de quien lo recibe (sale en el cierre de caja)
class TratamientoAbono extends Model
{
    use PerteneceATienda;

    protected $fillable = ['plan_id', 'numero', 'caja_id', 'user_id', 'metodo_pago_id', 'monto', 'saldo_despues', 'nota', 'anulado'];

    protected $casts = ['monto' => 'float', 'saldo_despues' => 'float', 'anulado' => 'boolean'];

    public function plan() { return $this->belongsTo(TratamientoPlan::class, 'plan_id'); }
    public function metodo() { return $this->belongsTo(MetodoPago::class, 'metodo_pago_id'); }
    public function user() { return $this->belongsTo(User::class); }

    public static function siguienteNumero(): string
    {
        $n = (int) static::query()->selectRaw("MAX(CAST(SUBSTRING(numero, 4) AS UNSIGNED)) m")->value('m');
        return 'AT-' . str_pad((string) ($n + 1), 6, '0', STR_PAD_LEFT);
    }
}
