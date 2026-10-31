<?php

namespace App\Support;

use App\Models\Alquiler;
use App\Models\Producto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Disponibilidad para alquilar: cuántas unidades tiene el negocio de cada producto (su cantidad en inventario),
// cuántas están ocupadas en esas fechas (cotizaciones aceptadas cuyas fechas se cruzan) y cuántas quedan libres.
// Una cotización aceptada deja de ocupar si su alquiler se canceló o ya se devolvió.
class Disponibilidad
{
    /**
     * @param int[] $productoIds
     * @return array<int, array{tiene: ?float, ocupado: float, libre: ?float, cotizaciones: array}>
     */
    public static function productos(array $productoIds, string|Carbon $desde, string|Carbon|null $hasta = null, ?int $excluir = null): array
    {
        $productoIds = array_values(array_unique(array_filter(array_map('intval', $productoIds))));
        if (!$productoIds) return [];
        $desde = Carbon::parse($desde)->toDateString();
        $hasta = $hasta ? Carbon::parse($hasta)->toDateString() : $desde;
        if ($hasta < $desde) $hasta = $desde;

        $productos = Producto::whereIn('id', $productoIds)->withSum(['variantes as unidades' => fn ($q) => $q->where('estado', 1)], 'stock')->get()->keyBy('id');

        $filas = DB::table('cotizacion_items as i')
            ->join('cotizaciones as c', 'c.id', '=', 'i.cotizacion_id')
            ->leftJoin('alquileres as a', 'a.id', '=', 'c.alquiler_id')
            ->where('c.tienda_id', TiendaActual::id())
            ->where('c.estado', 'aceptada')
            ->whereNotNull('c.evento_fecha')
            ->where('c.evento_fecha', '<=', $hasta)
            ->whereRaw('COALESCE(c.evento_hasta, c.evento_fecha) >= ?', [$desde])
            ->where(fn ($q) => $q->whereNull('a.id')->orWhere(fn ($x) => $x->where('a.estado', '!=', 'cancelado')->where(fn ($y) => $y->whereNull('a.entrega')->orWhere('a.entrega', '!=', 'devuelto'))))
            ->when($excluir, fn ($q) => $q->where('c.id', '!=', $excluir))
            ->whereIn('i.producto_id', $productoIds)
            ->get(['i.producto_id', 'i.cantidad', 'c.id', 'c.numero', 'c.cliente_nombre', 'c.evento_fecha', 'c.evento_hasta']);

        $res = [];
        foreach ($productoIds as $id) {
            $p = $productos->get($id);
            if (!$p) continue;
            $mias = $filas->where('producto_id', $id);
            $ocupado = round((float) $mias->sum('cantidad'), 3);
            $tiene = $p->controla_inventario ? round((float) $p->unidades, 3) : null;
            $res[$id] = [
                'nombre' => $p->nombre,
                'tiene' => $tiene,
                'ocupado' => $ocupado,
                'libre' => $tiene === null ? null : round(max($tiene - $ocupado, 0), 3),
                'cotizaciones' => $mias->groupBy('id')->map(fn ($g) => [
                    'numero' => $g->first()->numero,
                    'cliente' => $g->first()->cliente_nombre,
                    'cantidad' => round((float) $g->sum('cantidad'), 3),
                    'fechas' => Carbon::parse($g->first()->evento_fecha)->format('d/m') . ($g->first()->evento_hasta && $g->first()->evento_hasta !== $g->first()->evento_fecha ? ' al ' . Carbon::parse($g->first()->evento_hasta)->format('d/m') : ''),
                ])->values()->all(),
            ];
        }
        return $res;
    }

    // Alquileres registrados a mano para esas fechas (no están atados a productos: se muestran como referencia)
    public static function alquileres(string|Carbon $desde, string|Carbon|null $hasta = null)
    {
        $desde = Carbon::parse($desde)->toDateString();
        $hasta = $hasta ? Carbon::parse($hasta)->toDateString() : $desde;
        if (!TiendaActual::tienda()?->mod_alquiler) return collect();
        return Alquiler::where('estado', '!=', 'cancelado')
            ->where(fn ($q) => $q->whereBetween('fecha', [$desde, $hasta < $desde ? $desde : $hasta])
                ->orWhere(fn ($x) => $x->where('entrega', 'alquilado')->whereDate('fecha', '<=', $desde)))
            ->orderBy('fecha')->orderBy('hora')->limit(50)
            ->get(['id', 'numero', 'que_alquila', 'fecha', 'hora', 'lugar', 'entrega', 'estado', 'e360_token']);
    }
}
