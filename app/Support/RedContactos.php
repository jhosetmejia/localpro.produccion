<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\AsesorContacto;
use App\Models\RedContacto;
use Illuminate\Support\Facades\DB;

/**
 * Reparto de la base de LocalPro entre los asesores.
 * Regla: a cada persona se le escribe UNA sola vez. El que ya se contactó (por cualquier asesor, también desde
 * "Mi base de datos") no se le vuelve a dar a nadie, y lo que está asignado hoy a un asesor no le sale a otro.
 */
class RedContactos
{
    public static function diarios(): int
    {
        return max(1, min(200, (int) Ajuste::valor('red_contactos_diarios', 20)));
    }

    // Por contactar y sin asignar hoy a otro asesor
    public static function libres()
    {
        $hoy = now()->toDateString();
        return RedContacto::query()->whereNull('contactado_at')
            ->where(fn ($q) => $q->whereNull('asignado_fecha')->orWhere('asignado_fecha', '<', $hoy));
    }

    // Completa la cuota de hoy del asesor
    public static function asignarHoy(int $asesorId): void
    {
        $hoy = now()->toDateString();
        $faltan = self::diarios() - RedContacto::where('asignado_a', $asesorId)->where('asignado_fecha', $hoy)->count();
        if ($faltan <= 0) return;
        DB::transaction(function () use ($asesorId, $faltan, $hoy) {
            $ids = [];
            $desde = 0;
            while (count($ids) < $faltan) {
                $lote = self::libres()->where('id', '>', $desde)->orderBy('id')->limit(200)->lockForUpdate()->get(['id', 'telefono']);
                if ($lote->isEmpty()) break;
                $desde = $lote->last()->id;
                // Si algún asesor ya le escribió desde su propia base, se marca como contactado y no se reparte
                $yaEscritos = AsesorContacto::whereIn('telefono', $lote->pluck('telefono'))->whereNotNull('contactado_at')
                    ->get(['telefono', 'asesor_id', 'contactado_at', 'estado'])->keyBy('telefono');
                foreach ($lote as $c) {
                    if ($p = $yaEscritos->get($c->telefono)) {
                        RedContacto::whereKey($c->id)->update(['contactado_at' => $p->contactado_at, 'contactado_por' => $p->asesor_id, 'estado' => $p->estado ?: 'enviado']);
                        continue;
                    }
                    $ids[] = $c->id;
                    if (count($ids) >= $faltan) break;
                }
            }
            if ($ids) RedContacto::whereIn('id', $ids)->update(['asignado_a' => $asesorId, 'asignado_fecha' => $hoy]);
        });
    }

    // Cuando un asesor le escribe a alguien desde su propia base, ese número tampoco le sale a nadie en la base de LocalPro
    public static function marcarDesdePropia(string $telefono, int $asesorId, string $estado): void
    {
        RedContacto::where('telefono', $telefono)->whereNull('contactado_at')
            ->update(['contactado_at' => now(), 'contactado_por' => $asesorId, 'estado' => $estado]);
    }
}
