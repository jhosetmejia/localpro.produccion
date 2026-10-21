<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Pedido de restaurante.
 * - Modo "antes": el mesero lo toma, caja cobra y recién ahí llega a las estaciones (cocina, cafetería…).
 * - Modo "después": llega de una a las estaciones; el mesero puede agregar más y al final pide la cuenta.
 * Cada estación marca sus productos como listos; cuando todo está listo el mesero recibe el aviso y entrega.
 */
class Pedido extends Model
{
    use PerteneceATienda;

    protected $fillable = ['tienda_id', 'numero', 'cliente_nombre', 'mesa', 'nota', 'user_id', 'modo', 'estado', 'cuenta_pedida', 'total', 'pagado', 'pos_venta_id', 'cobrado_por', 'pagado_at', 'listo_at', 'entregado_at', 'cerrado_at'];

    protected $casts = ['total' => 'float', 'pagado' => 'boolean', 'cuenta_pedida' => 'boolean', 'pagado_at' => 'datetime', 'listo_at' => 'datetime', 'entregado_at' => 'datetime', 'cerrado_at' => 'datetime'];

    public const ESTADOS = [
        'por_cobrar' => ['Por cobrar', '#b45309', '#fef3c7'],
        'en_preparacion' => ['Preparando', '#1d4ed8', '#dbeafe'],
        'listo' => ['¡Listo para entregar!', '#11702f', '#dcfce7'],
        'entregado' => ['Entregado', '#475569', '#eef1ef'],
        'cerrado' => ['Cerrado', '#475569', '#eef1ef'],
        'anulado' => ['Anulado', '#b91c1c', '#fee2e2'],
    ];

    public function items()
    {
        return $this->hasMany(PedidoItem::class)->orderBy('ronda')->orderBy('id');
    }

    public function mesero()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function venta()
    {
        return $this->belongsTo(PosVenta::class, 'pos_venta_id');
    }

    public function getEstadoTxtAttribute(): string
    {
        return self::ESTADOS[$this->estado][0] ?? $this->estado;
    }

    // "#12 · Juan · Mesa 4"
    public function getTituloAttribute(): string
    {
        return '#' . $this->numero . ($this->cliente_nombre ? ' · ' . $this->cliente_nombre : '') . ($this->mesa ? ' · Mesa ' . $this->mesa : '');
    }

    public function getAbiertoAttribute(): bool
    {
        return !in_array($this->estado, ['cerrado', 'anulado'], true);
    }

    // Número del pedido: vuelve a 1 cada día
    public static function siguienteNumero(Tienda $tienda): int
    {
        $t = Tienda::whereKey($tienda->id)->lockForUpdate()->first();
        $n = $t->consecutivo_pedido_fecha && $t->consecutivo_pedido_fecha === today()->toDateString() ? (int) $t->consecutivo_pedido + 1 : 1;
        DB::table('tiendas')->where('id', $t->id)->update(['consecutivo_pedido' => $n, 'consecutivo_pedido_fecha' => today()->toDateString()]);
        return $n;
    }

    public function recalcularTotal(): void
    {
        $this->total = round((float) $this->items()->where('estado', '<>', 'anulado')->sum('subtotal'), 2);
    }

    // Pone el estado según cómo van sus productos
    public function recalcular(): void
    {
        if ($this->estado === 'anulado') return;
        $this->recalcularTotal();
        $vis = $this->items()->whereNotNull('enviado_at')->where('estado', '<>', 'anulado')->get(['estado']);
        $pend = $vis->where('estado', 'pendiente')->count();
        $listos = $vis->where('estado', 'listo')->count();

        if ($this->pagado && $pend === 0 && $listos === 0 && $vis->count() > 0) {
            $estado = 'cerrado';
        } elseif (!$this->pagado && ($this->modo === 'antes' || $this->cuenta_pedida)) {
            $estado = 'por_cobrar';
        } elseif ($pend > 0) {
            $estado = 'en_preparacion';
        } elseif ($listos > 0) {
            $estado = 'listo';
        } else {
            $estado = 'entregado';
        }
        if ($this->pagado && $vis->count() === 0) $estado = 'cerrado';
        if ($estado === 'listo' && $this->estado !== 'listo') $this->listo_at = now();
        if ($estado === 'cerrado' && !$this->cerrado_at) $this->cerrado_at = now();
        $this->estado = $estado;
        $this->save();
    }

    // Lo llama la caja al cobrar el pedido en el POS
    public function marcarPagado(PosVenta $venta): void
    {
        if ($this->pagado) return;
        $this->forceFill(['pagado' => true, 'pagado_at' => now(), 'pos_venta_id' => $venta->id, 'cobrado_por' => auth()->id()]);
        // Modo "antes": recién pagado pasa a las estaciones
        $this->items()->whereNull('enviado_at')->where('estado', '<>', 'anulado')->update(['enviado_at' => now()]);
        $this->recalcular();
    }

    // Lo que falta por mostrar en el tiempo: minutos desde que llegó a las estaciones
    public function getMinutosAttribute(): int
    {
        $desde = $this->items->whereNotNull('enviado_at')->min('enviado_at');
        return $desde ? (int) $desde->diffInMinutes(now()) : 0;
    }
}
