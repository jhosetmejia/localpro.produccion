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
        'efectivo_bs_esperado', 'efectivo_bs_contado',
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
        if ($abonos->isEmpty()) return self::soloEfectivoReal($ventas);
        // Suma ventas + abonos de separados por método (el dinero de los abonos entra a esta caja)
        return $ventas->concat($abonos)->groupBy('id')->map(function ($g) {
            $f = clone $g->first();
            $f->total = $g->sum('total');
            $f->ventas = $g->sum('ventas');
            $f->total_bs = $g->sum('total_bs');
            return $f;
        })->sortBy('nombre')->values()->pipe(fn ($c) => self::soloEfectivoReal($c));
    }

    /**
     * Abonos de separados y de alquileres recibidos en esta caja, por método.
     */
    public function abonosPorMetodo()
    {
        $filas = collect();
        foreach (['separado_abonos', 'alquiler_abonos'] as $tabla) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($tabla)) continue;
            $filas = $filas->concat(DB::table($tabla . ' as a')
                ->join('metodos_pago as m', 'm.id', '=', 'a.metodo_pago_id')
                ->where('a.caja_id', $this->id)
                ->where('a.anulado', false)
                ->groupBy('m.id', 'm.nombre', 'm.es_efectivo')
                ->select('m.id', 'm.nombre', 'm.es_efectivo', DB::raw('SUM(a.monto) as total'), DB::raw('COUNT(*) as ventas'), DB::raw('0 as total_bs'))
                ->addSelect(self::tieneMoneda() ? DB::raw('MAX(m.moneda) as moneda') : DB::raw('NULL as moneda'))
                ->get());
        }
        return $filas->groupBy('id')->map(function ($g) {
            $f = clone $g->first();
            $f->total = $g->sum('total');
            $f->ventas = $g->sum('ventas');
            $f->total_bs = $g->sum('total_bs');
            return $f;
        })->values();
    }

    private static function tieneMoneda(): bool
    {
        static $s = null;
        return $s ??= \Illuminate\Support\Facades\Schema::hasColumn('metodos_pago', 'moneda') && \Illuminate\Support\Facades\Schema::hasColumn('pos_venta_pagos', 'monto_moneda');
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
            // Venezuela: lo que entró en bolívares por cada método en Bs
            ->addSelect(self::tieneMoneda() ? [DB::raw('MAX(m.moneda) as moneda'), DB::raw('SUM(COALESCE(p.monto_moneda, 0)) as total_bs')] : [DB::raw('NULL as moneda'), DB::raw('0 as total_bs')])
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
            ->addSelect(self::tieneMoneda() ? DB::raw('MAX(m.moneda) as moneda') : DB::raw('NULL as moneda'))
            ->get()
            ->pipe(fn ($c) => self::soloEfectivoReal($c));
    }

    /**
     * Transferencias, Nequi, Daviplata, tarjetas, etc. nunca cuentan como efectivo,
     * aunque el método haya quedado marcado como "Es efectivo" por error.
     */
    private static function soloEfectivoReal($filas)
    {
        return $filas->map(function ($f) {
            $f->es_efectivo = (bool) $f->es_efectivo && !MetodoPago::esElectronico($f->nombre);
            return $f;
        });
    }

    /**
     * Cada movimiento de dinero del turno con su descripción, para saber de dónde sale cada valor:
     * ventas del POS, abonos de separados y de alquileres, devoluciones y egresos pagados con la caja.
     */
    public function movimientos()
    {
        $S = \Illuminate\Support\Facades\Schema::class;
        $filas = collect();

        $ventas = DB::table('pos_venta_pagos as p')
            ->join('pos_ventas as v', 'v.id', '=', 'p.pos_venta_id')
            ->join('metodos_pago as m', 'm.id', '=', 'p.metodo_pago_id')
            ->where('v.caja_id', $this->id)
            ->where('v.estado', 'pagada')
            ->when($S::hasColumn('metodos_pago', 'interno'), fn ($q) => $q->where('m.interno', false))
            ->when($S::hasTable('alquileres'), fn ($q) => $q->leftJoin('alquileres as al', 'al.pos_venta_id', '=', 'v.id'))
            ->select('p.monto', 'p.created_at', 'v.numero', 'v.cliente_nombre', 'm.nombre as metodo', 'm.es_efectivo')
            ->when($S::hasTable('alquileres'), fn ($q) => $q->addSelect(array_merge(['al.numero as alq_numero', 'al.que_alquila'], self::columnasAlquiler())))
            ->get();
        foreach ($ventas as $r) {
            $filas->push((object) [
                'hora' => $r->created_at, 'tipo' => 'Venta POS', 'numero' => $r->numero,
                'detalle' => !empty($r->alq_numero) ? 'Cobro del alquiler ' . $r->alq_numero . ' · ' . $r->que_alquila : ($r->cliente_nombre ?: 'Mostrador'),
                'extra' => !empty($r->alq_numero) ? self::textoAlquiler($r) : null,
                'nombre' => $r->metodo, 'es_efectivo' => $r->es_efectivo, 'monto' => (float) $r->monto,
            ]);
        }

        if ($S::hasTable('separado_abonos')) {
            $abonos = DB::table('separado_abonos as a')
                ->join('separados as s', 's.id', '=', 'a.separado_id')
                ->join('metodos_pago as m', 'm.id', '=', 'a.metodo_pago_id')
                ->where('a.caja_id', $this->id)->where('a.anulado', false)
                ->select('a.monto', 'a.created_at', 'a.numero', 'a.nota', 's.numero as ref', 's.cliente_nombre', 'm.nombre as metodo', 'm.es_efectivo')
                ->get();
            foreach ($abonos as $r) {
                $filas->push((object) [
                    'hora' => $r->created_at, 'tipo' => 'Abono de separado', 'numero' => $r->numero,
                    'detalle' => 'Separado ' . $r->ref . ' · ' . $r->cliente_nombre . ($r->nota ? ' · ' . $r->nota : ''),
                    'nombre' => $r->metodo, 'es_efectivo' => $r->es_efectivo, 'monto' => (float) $r->monto,
                ]);
            }
        }

        if ($S::hasTable('alquiler_abonos')) {
            $abonos = DB::table('alquiler_abonos as a')
                ->join('alquileres as al', 'al.id', '=', 'a.alquiler_id')
                ->join('metodos_pago as m', 'm.id', '=', 'a.metodo_pago_id')
                ->where('a.caja_id', $this->id)->where('a.anulado', false)
                ->select(array_merge(['a.monto', 'a.created_at', 'a.numero', 'a.nota', 'al.numero as ref', 'al.que_alquila', 'm.nombre as metodo', 'm.es_efectivo'], self::columnasAlquiler()))
                ->get();
            foreach ($abonos as $r) {
                $filas->push((object) [
                    'hora' => $r->created_at, 'tipo' => $r->monto < 0 ? 'Devolución de alquiler' : 'Abono de alquiler', 'numero' => $r->numero,
                    'detalle' => 'Alquiler ' . $r->ref . ' · ' . $r->que_alquila . ($r->nota ? ' · ' . $r->nota : ''),
                    'extra' => self::textoAlquiler($r),
                    'nombre' => $r->metodo, 'es_efectivo' => $r->es_efectivo, 'monto' => (float) $r->monto,
                ]);
            }
        }

        $devs = DB::table('devoluciones as d')
            ->join('pos_ventas as v', 'v.id', '=', 'd.pos_venta_id')
            ->join('metodos_pago as m', 'm.id', '=', 'd.metodo_pago_id')
            ->where('d.caja_id', $this->id)->where('d.tipo', 'reembolso')
            ->select('d.total', 'd.created_at', 'd.codigo', 'd.motivo', 'v.numero as ref', 'm.nombre as metodo', 'm.es_efectivo')
            ->get();
        foreach ($devs as $r) {
            $filas->push((object) [
                'hora' => $r->created_at, 'tipo' => 'Devolución', 'numero' => $r->codigo,
                'detalle' => 'De la venta ' . $r->ref . ($r->motivo ? ' · ' . $r->motivo : ''),
                'nombre' => $r->metodo, 'es_efectivo' => $r->es_efectivo, 'monto' => -(float) $r->total,
            ]);
        }

        // Egresos pagados en efectivo con la plata de esta caja
        if ($S::hasTable('egresos')) {
            $egs = DB::table('egresos as e')
                ->leftJoin('users as u', 'u.id', '=', 'e.user_id')
                ->where('e.caja_id', $this->id)
                ->select('e.valor', 'e.created_at', 'e.id', 'e.concepto', 'u.name as usuario')
                ->get();
            foreach ($egs as $r) {
                $filas->push((object) [
                    'hora' => $r->created_at, 'tipo' => 'Egreso', 'numero' => 'EG-' . $r->id,
                    'detalle' => $r->concepto . ($r->usuario ? ' · ' . $r->usuario : ''),
                    'nombre' => 'Efectivo de la caja', 'es_efectivo' => true, 'monto' => -(float) $r->valor, 'egreso' => true,
                ]);
            }
        }

        return self::soloEfectivoReal($filas->map(function ($f) { $f->extra ??= null; return $f; }))
            ->map(function ($f) { if (!empty($f->egreso)) $f->es_efectivo = true; return $f; })
            ->sortBy('hora')->values();
    }

    // Datos del evento que se muestran en el detalle de la caja (solo las columnas que existan)
    private static function columnasAlquiler(): array
    {
        static $cols = null;
        return $cols ??= collect(['fecha', 'hora', 'hora_fin', 'lugar', 'responsable_telefono', 'encargado_nombre', 'encargado_telefono'])
            ->filter(fn ($c) => \Illuminate\Support\Facades\Schema::hasColumn('alquileres', $c))
            ->map(fn ($c) => 'al.' . $c . ' as alq_' . $c)->values()->all();
    }

    // "31/10/2026 · 12:00 pm a 6:00 pm · Lugar: … · Contrata: … · Encargado: …"
    private static function textoAlquiler($r): string
    {
        $h = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('g:i a') : null;
        $horario = $h($r->alq_hora ?? null);
        if ($horario && ($fin = $h($r->alq_hora_fin ?? null))) $horario .= ' a ' . $fin;
        return collect([
            !empty($r->alq_fecha) ? 'Evento ' . \Carbon\Carbon::parse($r->alq_fecha)->format('d/m/Y') : null,
            $horario,
            !empty($r->alq_lugar) ? 'Lugar: ' . $r->alq_lugar : null,
            !empty($r->alq_responsable_telefono) ? 'Contrata: ' . $r->alq_responsable_telefono : null,
            !empty($r->alq_encargado_nombre) ? 'Encargado: ' . $r->alq_encargado_nombre . (!empty($r->alq_encargado_telefono) ? ' (' . $r->alq_encargado_telefono . ')' : '') : 'Sin encargado asignado',
        ])->filter()->implode(' · ');
    }

    // Efectivo en la moneda de la tienda (en Venezuela: dólares; el efectivo en bolívares se cuenta aparte)
    public function efectivoEsperado(): float
    {
        $efectivo = $this->totalesPorMetodo()->where('es_efectivo', true)->filter(fn ($t) => ($t->moneda ?? null) !== 'VES')->sum('total');
        $devuelto = $this->reembolsosPorMetodo()->where('es_efectivo', true)->filter(fn ($t) => ($t->moneda ?? null) !== 'VES')->sum('total');
        return (float) $this->monto_inicial + (float) $efectivo - (float) $devuelto - $this->totalEgresos();
    }

    // Venezuela: bolívares en efectivo que deben estar en la caja (lo recibido en Bs; abonos y devoluciones a la tasa de hoy)
    public function efectivoBsEsperado(): float
    {
        if (!self::tieneMoneda()) return 0.0;
        $tasa = (float) (\App\Support\TiendaActual::tienda()?->tasa_bs_vigente ?? 0);
        $bs = $this->totalesVentas()->where('es_efectivo', true)->where('moneda', 'VES')->sum('total_bs');
        $abonos = $this->abonosPorMetodo()->where('es_efectivo', true)->where('moneda', 'VES')->sum('total') * $tasa;
        $devuelto = $this->reembolsosPorMetodo()->where('es_efectivo', true)->where('moneda', 'VES')->sum('total') * $tasa;
        return round((float) $bs + (float) $abonos - (float) $devuelto, 2);
    }

    // ¿Esta caja maneja efectivo en bolívares?
    public function manejaBs(): bool
    {
        return self::tieneMoneda() && \App\Support\TiendaActual::tienda()?->pais === 'VE'
            && MetodoPago::where('activo', true)->where('es_efectivo', true)->where('moneda', 'VES')->exists();
    }

    // Egresos pagados en efectivo con la plata de esta caja (se descuentan de lo que debe haber)
    public function egresos()
    {
        return $this->hasMany(Egreso::class);
    }

    public function totalEgresos(): float
    {
        return \Illuminate\Support\Facades\Schema::hasTable('egresos') ? (float) $this->egresos()->sum('valor') : 0.0;
    }
}
