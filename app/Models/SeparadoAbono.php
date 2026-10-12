<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class SeparadoAbono extends Model
{
    use PerteneceATienda;

    protected $table = 'separado_abonos';

    protected $fillable = ['separado_id', 'numero', 'caja_id', 'user_id', 'metodo_pago_id', 'monto', 'saldo_despues', 'nota', 'anulado'];

    protected $casts = ['monto' => 'float', 'saldo_despues' => 'float', 'anulado' => 'boolean'];

    public function separado() { return $this->belongsTo(Separado::class); }
    public function metodo() { return $this->belongsTo(MetodoPago::class, 'metodo_pago_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
