<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\Tienda;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * "LocalPro gratis": 1 mes gratis del plan por cada emprendedor que se une con el link del negocio
 * (localproo.com/r/LCT-00001) y cumple las reglas. Las reglas evitan cuentas falsas:
 *  1. Se registró con el link o el código del negocio que invita.
 *  2. Completó los primeros pasos de LocalPro (logo y WhatsApp, 3 productos con foto, tienda en línea activa,
 *     10 clientes, 5 clientes contactados y su primera venta).
 *  3. Su WhatsApp es propio: distinto al del negocio que invita y no lo usa otro negocio en LocalPro.
 *  4. Lleva al menos 7 días registrado y ha usado LocalPro en 3 días distintos.
 *  5. No está suspendido.
 * El mes se suma al plan del que invita (si ya venció, cuenta desde hoy). Máximo `ref_meses_max` meses por año (12).
 */
class ReferidosMes
{
    public const DIAS_PREMIO = 30;
    public const DIAS_MINIMOS = 7;
    public const DIAS_DE_USO = 3;

    public static function maximoAnual(): int
    {
        return max(0, (int) Ajuste::valor('ref_meses_max', 12));
    }

    public static function mesesEsteAnio(Tienda $ref): int
    {
        return (int) DB::table('referido_meses')->where('tienda_id', $ref->id)->where('created_at', '>=', now()->subYear())->count();
    }

    private static function ultimos10(?string $tel): string
    {
        return substr(preg_replace('/\D/', '', (string) $tel), -10);
    }

    // Revisa las reglas para un invitado. Devuelve las reglas con su estado y si ya ganó el mes.
    public static function estado(Tienda $invitado, Tienda $ref): array
    {
        $ganado = DB::table('referido_meses')->where('referida_id', $invitado->id)->first();

        // Primeros pasos del invitado (se miden en su propio negocio)
        $antes = TiendaActual::id();
        try {
            TiendaActual::set($invitado->id);
            $res = PrimerosPasos::resumen();
        } catch (\Throwable $e) {
            Log::warning('ReferidosMes: no se pudieron revisar los pasos', ['tienda' => $invitado->id, 'error' => $e->getMessage()]);
            $res = ['hechos' => 0, 'total' => 6];
        } finally {
            TiendaActual::set($antes);
        }

        $wa = self::ultimos10($invitado->telefono);
        $waPropio = strlen($wa) >= 7 && $wa !== self::ultimos10($ref->telefono)
            && !Tienda::where('id', '!=', $invitado->id)->whereNotNull('telefono')->get(['id', 'telefono'])
                ->contains(fn ($o) => self::ultimos10($o->telefono) === $wa);

        $dias = (int) $invitado->created_at->copy()->startOfDay()->diffInDays(today());
        $diasUso = self::diasDeUso($invitado->id);

        $reglas = [
            ['ok' => $res['hechos'] >= $res['total'], 'txt' => 'Primeros pasos completos', 'det' => $res['hechos'] . ' de ' . $res['total']],
            ['ok' => $waPropio, 'txt' => 'WhatsApp propio del negocio', 'det' => $wa ? ($waPropio ? 'Listo' : 'Repetido con otro negocio') : 'Falta el WhatsApp'],
            ['ok' => $dias >= self::DIAS_MINIMOS, 'txt' => self::DIAS_MINIMOS . ' días desde que se unió', 'det' => min($dias, self::DIAS_MINIMOS) . ' de ' . self::DIAS_MINIMOS],
            ['ok' => $diasUso >= self::DIAS_DE_USO, 'txt' => 'Usó LocalPro en ' . self::DIAS_DE_USO . ' días distintos', 'det' => min($diasUso, self::DIAS_DE_USO) . ' de ' . self::DIAS_DE_USO],
            ['ok' => ($invitado->estado ?? 'activa') !== 'suspendida', 'txt' => 'Cuenta activa', 'det' => ($invitado->estado ?? 'activa') === 'suspendida' ? 'Suspendida' : 'Listo'],
        ];
        $cumple = collect($reglas)->every(fn ($r) => $r['ok']);
        return ['reglas' => $reglas, 'cumple' => $cumple, 'ganado' => $ganado, 'hechas' => collect($reglas)->where('ok', true)->count(), 'total' => count($reglas)];
    }

    // Días distintos en que el negocio creó productos, clientes o ventas
    public static function diasDeUso(int $tiendaId): int
    {
        $q = fn ($tabla) => DB::table($tabla)->where('tienda_id', $tiendaId)->selectRaw('DATE(created_at) as d');
        $u = $q('productos')->union($q('clientes'))->union($q('pos_ventas'));
        return (int) DB::query()->fromSub($u, 'x')->distinct()->count('d');
    }

    // Invitados del negocio con su estado (y entrega los meses que ya se ganaron)
    public static function revisar(Tienda $ref): array
    {
        $invitados = Tienda::where('referida_por_tienda_id', $ref->id)->where('id', '!=', $ref->id)->orderByDesc('id')->get();
        $out = [];
        $nuevos = 0;
        foreach ($invitados as $i) {
            $e = self::estado($i, $ref);
            if ($e['cumple'] && !$e['ganado'] && self::otorgar($ref, $i)) {
                $e['ganado'] = DB::table('referido_meses')->where('referida_id', $i->id)->first();
                $nuevos++;
            }
            $out[] = ['tienda' => $i] + $e;
        }
        if ($nuevos) $ref->refresh();
        return ['invitados' => $out, 'nuevos' => $nuevos, 'ganados' => collect($out)->filter(fn ($x) => $x['ganado'])->count()];
    }

    // Suma 1 mes al plan del negocio que invitó (una sola vez por invitado)
    public static function otorgar(Tienda $ref, Tienda $invitado): bool
    {
        if (($ref->estado ?? 'activa') === 'suspendida') return false;
        if (self::mesesEsteAnio($ref) >= self::maximoAnual()) return false;
        try {
            return DB::transaction(function () use ($ref, $invitado) {
                $t = Tienda::whereKey($ref->id)->lockForUpdate()->first();
                if (DB::table('referido_meses')->where('referida_id', $invitado->id)->exists()) return false;
                $antes = $t->plan_vence?->copy();
                // Vigente: se suma al final. Vencido: cuenta desde hoy. Sin vencimiento: queda registrado a favor.
                $nuevo = $antes ? ($antes->gte(today()) ? $antes->copy()->addDays(self::DIAS_PREMIO) : today()->addDays(self::DIAS_PREMIO - 1)) : null;
                if ($nuevo) {
                    $cambios = ['plan_vence' => $nuevo];
                    if (!$t->plan_id && ($prueba = \App\Models\Plan::prueba())) $cambios['plan_id'] = $prueba->id;
                    $t->forceFill($cambios)->save();
                }
                DB::table('referido_meses')->insert([
                    'tienda_id' => $t->id, 'referida_id' => $invitado->id, 'dias' => self::DIAS_PREMIO,
                    'vence_antes' => $antes?->toDateString(), 'vence_nuevo' => $nuevo?->toDateString(),
                    'nota' => $invitado->nombre . ' se unió con su link y completó los pasos' . ($nuevo ? '' : ' (su plan no vence: el mes queda a favor)'),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                return true;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return false; // otro proceso ya lo entregó (referida_id único)
        }
    }

    // Texto para compartir el link (WhatsApp)
    public static function mensaje(Tienda $t): string
    {
        return "¡Hola! 👋 Yo manejo mi negocio con *LocalProo*: ventas, caja, inventario y mi tienda en línea, todo desde el celular.\n\n"
            . "Te lo recomiendo 💚 Regístrate con mi link y tienes *2 meses gratis* con todo:\n👉 " . Referidos::enlace($t);
    }
}
