<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Caja extends Model
{
    use PerteneceATienda;

    protected $table = 'cajas';

    protected $fillable = [
        'user_id', 'abierta_en', 'cerrada_en', 'monto_inicial', 'total_ventas',
        'efectivo_esperado', 'efectivo_contado', 'diferencia', 'nota_apertura', 'nota_cierre', 'estado',
    ];

    protected $casts = ['abierta_en' => 'datetime', 'cerrada_en' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ventas()
    {
        return $this->hasMany(PosVenta::class);
    }

    public static function abiertaDe(int $userId): ?self
    {
        return static::where('user_id', $userId)->where('estado', 'abierta')->latest('id')->first();
    }

    /**
     * Totales cobrados por método de pago (solo ventas no anuladas).
     */
    public function totalesPorMetodo()
    {
        $ventas = $this->totalesVentas();
        $abonos = $this->abonosPorMetodo();
        if ($abonos->isEmpty()) return $ventas;
        // Suma ventas + abonos de separados por método (el dinero de los abonos entra a esta caja)
        return $ventas->concat($abonos)->groupBy('id')->map(function ($g) {
            $f = clone $g->first();
            $f->total = $g->sum('total');
            $f->ventas = $g->sum('ventas');
            return $f;
        })->sortBy('nombre')->values();
    }

    /**
     * Abonos de separados recibidos en esta caja, por método.
     */
    public function abonosPorMetodo()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('separado_abonos')) return collect();
        return DB::table('separado_abonos as a')
            ->join('metodos_pago as m', 'm.id', '=', 'a.metodo_pago_id')
            ->where('a.caja_id', $this->id)
            ->where('a.anulado', false)
            ->groupBy('m.id', 'm.nombre', 'm.es_efectivo')
            ->select('m.id', 'm.nombre', 'm.es_efectivo', DB::raw('SUM(a.monto) as total'), DB::raw('COUNT(*) as ventas'))
            ->get();
    }

    public function totalAbonos(): float
    {
        return (float) $this->abonosPorMetodo()->sum('total');
    }

    private function totalesVentas()
    {
        return DB::table('pos_venta_pagos as p')
            ->join('pos_ventas as v', 'v.id', '=', 'p.pos_venta_id')
            ->join('metodos_pago as m', 'm.id', '=', 'p.metodo_pago_id')
            ->where('v.caja_id', $this->id)
            ->where('v.estado', 'pagada')
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('metodos_pago', 'interno'), fn ($q) => $q->where('m.interno', false))
            ->groupBy('m.id', 'm.nombre', 'm.es_efectivo')
            ->orderBy('m.nombre')
            ->select('m.id', 'm.nombre', 'm.es_efectivo', DB::raw('SUM(p.monto) as total'), DB::raw('COUNT(DISTINCT v.id) as ventas'))
            ->get();
    }

    /**
     * Dinero devuelto a clientes desde esta caja, por método (solo reembolsos; los cambios no sacan dinero).
     */
    public function reembolsosPorMetodo()
    {
        return DB::table('devoluciones as d')
            ->join('metodos_pago as m', 'm.id', '=', 'd.metodo_pago_id')
            ->where('d.caja_id', $this->id)
            ->where('d.tipo', 'reembolso')
            ->groupBy('m.id', 'm.nombre', 'm.es_efectivo')
            ->select('m.id', 'm.nombre', 'm.es_efectivo', DB::raw('SUM(d.total) as total'), DB::raw('COUNT(*) as n'))
            ->get();
    }

    public function efectivoEsperado(): float
    {
        $efectivo = $this->totalesPorMetodo()->where('es_efectivo', 1)->sum('total');
        $devuelto = $this->reembolsosPorMetodo()->where('es_efectivo', 1)->sum('total');
        return (float) $this->monto_inicial + (float) $efectivo - (float) $devuelto;
    }
}
