<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\Asesor;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de crecimiento de la red comercial:
 * - Aspirantes: entran por el enlace de quien los invita y él los aprueba.
 * - Subir de rango: 10 negocios activos que pagaron su plan anual + 10 asesores reclutados activos (configurable).
 *   Las pruebas gratis NO cuentan. El sistema sugiere el ascenso y el administrador lo aprueba.
 */
class RedComercial
{
    public const ORDEN = ['asesor', 'lider_zona', 'lider_ciudad', 'lider_departamento', 'lider_pais'];

    public static function metas(): array
    {
        return [
            'negocios' => (int) Ajuste::valor('red_meta_negocios', 10),
            'asesores' => (int) Ajuste::valor('red_meta_asesores', 10),
        ];
    }

    public static function siguiente(string $cargo): ?string
    {
        $i = array_search($cargo, self::ORDEN, true);
        return $i === false ? null : (self::ORDEN[$i + 1] ?? null);
    }

    // Negocios de cada asesor con plan al día que SÍ pagaron un plan anual (no cuentan pruebas ni gratis)
    public static function negociosPagados(array $ids): array
    {
        if (!$ids) return [];
        return DB::table('tiendas as t')
            ->whereIn('t.asesor_id', $ids)
            ->whereNotNull('t.plan_vence')->where('t.plan_vence', '>=', today())
            ->whereExists(function ($q) {
                $q->from('pagos_suscripcion as p')->join('planes as pl', 'pl.id', '=', 'p.plan_id')
                    ->whereColumn('p.tienda_id', 't.id')->where('p.estado', 'aprobado')->where('p.monto', '>', 0)
                    ->whereBetween('pl.dias', [360, 400]);
            })
            ->groupBy('t.asesor_id')->select('t.asesor_id', DB::raw('COUNT(*) as n'))
            ->pluck('n', 'asesor_id')->map(fn ($n) => (int) $n)->all();
    }

    // Personas que cada uno reclutó y están activas
    public static function reclutadosActivos(array $ids): array
    {
        if (!$ids) return [];
        return Asesor::whereIn('reclutado_por', $ids)->where('activo', true)->where('estado', 'activo')
            ->groupBy('reclutado_por')->select('reclutado_por', DB::raw('COUNT(*) as n'))
            ->pluck('n', 'reclutado_por')->map(fn ($n) => (int) $n)->all();
    }

    public static function progreso(Asesor $a): array
    {
        $m = self::metas();
        $n = self::negociosPagados([$a->id])[$a->id] ?? 0;
        $r = self::reclutadosActivos([$a->id])[$a->id] ?? 0;
        $sig = self::siguiente($a->cargo);
        return [
            'negocios' => $n, 'asesores' => $r, 'metaN' => $m['negocios'], 'metaA' => $m['asesores'],
            'siguiente' => $sig, 'siguienteNombre' => $sig ? Asesor::CARGOS[$sig][0] : null,
            'listo' => $sig && $n >= $m['negocios'] && $r >= $m['asesores'],
        ];
    }

    // Personas que ya cumplen la meta para subir de rango
    public static function candidatos()
    {
        $m = self::metas();
        $gente = Asesor::with('user:id,name,telefono')->where('activo', true)->where('estado', 'activo')
            ->where('cargo', '<>', 'lider_pais')->get();
        $ids = $gente->pluck('id')->all();
        $neg = self::negociosPagados($ids);
        $rec = self::reclutadosActivos($ids);
        return $gente->map(function ($a) use ($neg, $rec, $m) {
            $a->meta_negocios = $neg[$a->id] ?? 0;
            $a->meta_asesores = $rec[$a->id] ?? 0;
            $a->cargo_siguiente = self::siguiente($a->cargo);
            return $a;
        })->filter(fn ($a) => $a->meta_negocios >= $m['negocios'] && $a->meta_asesores >= $m['asesores'])->values();
    }

    // Dónde queda en la red un aspirante aprobado
    public static function ubicar(Asesor $nuevo, Asesor $invita): ?int
    {
        $tieneCupo = fn (Asesor $l) => $l->cargo !== 'lider_zona'
            || Asesor::where('padre_id', $l->id)->where('cargo', 'asesor')->where('activo', true)->count() < Asesor::MAX_ASESORES_ZONA;
        if ($invita->nivel > 1) {
            return $tieneCupo($invita) ? $invita->id : ($invita->padre_id ?: null);
        }
        // Lo invitó un asesor: queda con el mismo líder de quien lo invitó (si tiene cupo)
        $lider = $invita->padre;
        return $lider && $tieneCupo($lider) ? $lider->id : ($lider?->padre_id ?: null);
    }

    // Sube de rango: nuevo cargo, se acomoda su líder y se le pasan las personas que él reclutó
    public static function ascender(Asesor $a, ?int $porUsuario = null): string
    {
        $sig = self::siguiente($a->cargo);
        if (!$sig) return $a->cargo;
        $nivel = Asesor::CARGOS[$sig][2];
        $padreAnterior = $a->padre_id;

        $padre = $a->padre;
        $vistos = [];
        while ($padre && $padre->nivel <= $nivel && !isset($vistos[$padre->id])) {
            $vistos[$padre->id] = true;
            $padre = $padre->padre;
        }
        $a->update(['cargo' => $sig, 'padre_id' => $sig === 'lider_pais' ? null : $padre?->id]);

        // Sus reclutados que estaban con su antiguo líder (o sin líder) pasan a su equipo
        $movidos = 0;
        $reclutados = Asesor::where('reclutado_por', $a->id)->where('activo', true)->where('estado', 'activo')
            ->where(fn ($q) => $q->whereNull('padre_id')->orWhere('padre_id', $padreAnterior))->get();
        foreach ($reclutados as $r) {
            if ($r->nivel >= $nivel) continue;
            if ($sig === 'lider_zona' && $r->cargo === 'asesor'
                && Asesor::where('padre_id', $a->id)->where('cargo', 'asesor')->where('activo', true)->count() >= Asesor::MAX_ASESORES_ZONA) {
                break;
            }
            $r->update(['padre_id' => $a->id]);
            $movidos++;
        }

        self::historial($a, 'ascenso', 'Subió a ' . Asesor::CARGOS[$sig][0] . ($movidos ? " · $movidos persona(s) pasaron a su equipo" : ''), $porUsuario);
        return $sig;
    }

    public static function historial(Asesor $a, string $accion, ?string $detalle = null, ?int $usuario = null): void
    {
        DB::table('asesor_historial')->insert([
            'asesor_id' => $a->id, 'accion' => $accion, 'detalle' => $detalle ? mb_substr($detalle, 0, 255) : null,
            'user_id' => $usuario ?? auth()->id(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
