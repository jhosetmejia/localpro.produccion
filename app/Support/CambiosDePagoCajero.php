<?php

namespace App\Support;

use App\Http\Controllers\correccionVentaController;
use App\Models\PosVentaCorreccion;
use App\Models\User;

// Campanita: el dueño/administrador ve cuando un cajero cambió la forma de pago de una venta (últimos 7 días)
class CambiosDePagoCajero
{
    public const DIAS = 7;

    public static function notificaciones(User $u): array
    {
        if (!TiendaActual::id() || !correccionVentaController::esAdmin()) return [];
        $lista = [];
        $filas = PosVentaCorreccion::with(['user:id,name', 'venta:id,numero'])
            ->where('created_at', '>=', now()->subDays(self::DIAS))
            ->latest('id')->limit(60)->get();
        foreach ($filas as $c) {
            if (empty($c->detalle['por_cajero'])) continue;
            $cambios = collect($c->detalle['metodos'] ?? [])->map(fn ($d) => e($d['antes']) . ' → ' . e($d['despues']) . ' (' . e(dinero($d['monto'])) . ')')->implode(', ');
            $lista[] = [
                'clave' => 'cambio-pago-' . $c->id,
                'icono' => 'fa-money-bill-transfer',
                'color' => 'naranja',
                'prioridad' => 2,
                'texto' => '<b>' . e($c->user->name ?? 'Un cajero') . '</b> cambió la forma de pago de la venta <b>' . e($c->venta->numero ?? '') . '</b>: ' . $cambios . '. <span class="lp-noti-tienda">Motivo: ' . e($c->motivo) . '</span>',
                'cuando' => ucfirst($c->created_at->locale('es')->diffForHumans()),
                'fecha' => $c->created_at->format('Y-m-d H:i'),
                'url' => $c->pos_venta_id ? route('pos.corregir', $c->pos_venta_id) : null,
            ];
        }
        return $lista;
    }
}
