<?php

namespace App\Support;

use App\Models\Asesor;
use App\Models\AsesorContacto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Un líder (o cualquier asesor con gente en su red) comparte "Mi base de datos" con los asesores que sumó o tiene debajo.
 * Solo se mueve dentro de la red comercial de LocalPro: nunca toca las bases de datos de los negocios.
 * Regla: a cada número se le escribe UNA sola vez (lo escriba el líder o cualquiera de su gente).
 */
class BaseLider
{
    // Líderes "de arriba" del asesor: su líder, el líder de su líder… y quien lo sumó
    public static function deArriba(Asesor $a): array
    {
        $ids = [];
        $nivel = [$a->padre_id, $a->reclutado_por];
        for ($i = 0; $i < 10; $i++) {
            $nivel = array_values(array_diff(array_filter(array_unique($nivel)), $ids, [$a->id]));
            if (!$nivel) break;
            $ids = array_merge($ids, $nivel);
            $nivel = Asesor::whereIn('id', $nivel)->get(['padre_id', 'reclutado_por'])
                ->flatMap(fn ($x) => [$x->padre_id, $x->reclutado_por])->all();
        }
        return $ids;
    }

    // Líderes que le comparten su base de datos a este asesor
    public static function lideresPara(Asesor $a): Collection
    {
        if (!$a->activo || !$a->curso_aprobado_at) return collect();
        $arriba = self::deArriba($a);
        if (!$arriba) return collect();
        return Asesor::with('user:id,name')->whereIn('id', $arriba)->where('compartir_contactos', true)->where('activo', true)->get();
    }

    public static function puede(Asesor $a, int $liderId): bool
    {
        return self::lideresPara($a)->contains('id', $liderId);
    }

    // Por escribir y sin asignar hoy a otra persona
    public static function libres(int $liderId)
    {
        $hoy = now()->toDateString();
        return AsesorContacto::where('asesor_id', $liderId)->whereNull('contactado_at')
            ->where(fn ($q) => $q->whereNull('asignado_fecha')->orWhere('asignado_fecha', '<', $hoy));
    }

    // Completa la cuota de hoy del asesor con contactos de la base de su líder
    public static function asignarHoy(int $liderId, int $asesorId, int $cuota): void
    {
        $hoy = now()->toDateString();
        $faltan = $cuota - AsesorContacto::where('asesor_id', $liderId)->where('asignado_a', $asesorId)->where('asignado_fecha', $hoy)->count();
        if ($faltan <= 0) return;
        DB::transaction(function () use ($liderId, $asesorId, $faltan, $hoy) {
            $ids = self::libres($liderId)->orderBy('id')->limit($faltan)->lockForUpdate()->pluck('id');
            if ($ids->isNotEmpty()) AsesorContacto::whereIn('id', $ids)->update(['asignado_a' => $asesorId, 'asignado_fecha' => $hoy]);
        });
    }
}
