<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Caja extends Model
{
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
        return DB::table('pos_venta_pagos as p')
            ->join('pos_ventas as v', 'v.id', '=', 'p.pos_venta_id')
            ->join('metodos_pago as m', 'm.id', '=', 'p.metodo_pago_id')
            ->where('v.caja_id', $this->id)
            ->where('v.estado', 'pagada')
            ->groupBy('m.id', 'm.nombre', 'm.es_efectivo')
            ->orderBy('m.nombre')
            ->select('m.id', 'm.nombre', 'm.es_efectivo', DB::raw('SUM(p.monto) as total'), DB::raw('COUNT(DISTINCT v.id) as ventas'))
            ->get();
    }

    public function efectivoEsperado(): float
    {
        $efectivo = $this->totalesPorMetodo()->where('es_efectivo', 1)->sum('total');
        return (float) $this->monto_inicial + (float) $efectivo;
    }
}
