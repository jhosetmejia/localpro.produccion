<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    use PerteneceATienda;

    protected $table = 'metodos_pago';

    protected $fillable = ['nombre', 'medio_dian', 'es_efectivo', 'es_credito', 'activo', 'interno', 'orden'];

    protected $casts = ['es_efectivo' => 'boolean', 'es_credito' => 'boolean', 'activo' => 'boolean'];

    // Medio de pago para la DIAN: si no lo eligen, se deduce del nombre
    protected static function booted(): void
    {
        static::saving(function (self $m) {
            // Un método electrónico (transferencia, Nequi, tarjeta…) nunca puede ser efectivo
            if ($m->es_efectivo && self::esElectronico($m->nombre)) $m->es_efectivo = false;
            static $hay = null;
            $hay ??= \Illuminate\Support\Facades\Schema::hasColumn('metodos_pago', 'medio_dian');
            if (!$hay) return;
            if (!$m->medio_dian || $m->isDirty('es_efectivo')) $m->medio_dian = $m->es_efectivo ? '10' : ($m->medio_dian && $m->medio_dian !== '10' ? $m->medio_dian : self::medioDian($m->nombre));
        });
    }

    public static function medioDian(?string $nombre): string
    {
        $n = mb_strtolower((string) $nombre);
        return match (true) {
            str_contains($n, 'efectivo') => '10',
            str_contains($n, 'débito') || str_contains($n, 'debito') => '49',
            str_contains($n, 'tarjeta') || str_contains($n, 'crédito') || str_contains($n, 'credito') || str_contains($n, 'datáfono') || str_contains($n, 'datafono') => '48',
            str_contains($n, 'nequi') || str_contains($n, 'daviplata') || str_contains($n, 'transfer') || str_contains($n, 'bancolombia') || str_contains($n, 'pse') || str_contains($n, 'qr') => '47',
            str_contains($n, 'consigna') => '42',
            default => 'ZZZ',
        };
    }

    // Transferencias, billeteras, tarjetas y consignaciones: dinero que no entra al cajón
    public static function esElectronico(?string $nombre): bool
    {
        return in_array(self::medioDian($nombre), ['42', '47', '48', '49'], true);
    }

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
