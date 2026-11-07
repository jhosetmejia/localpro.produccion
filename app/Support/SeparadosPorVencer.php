<?php

namespace App\Support;

use App\Http\Controllers\separadoController;
use App\Models\Separado;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

// Campanita de separados por vencer: para el dueño/equipo del negocio y para el asesor que trajo el negocio
class SeparadosPorVencer
{
    public const DIAS_ANTES = 5;   // la campanita avisa desde 5 días antes
    public const DIAS_DESPUES = 15; // y sigue mostrando los vencidos hasta 15 días después
    public const DIAS_CORREO = 3;   // correo al cliente 3 días antes, el mismo día y 1 día después

    // Separados activos, con saldo y que vencen pronto (o vencieron hace poco)
    public static function consulta()
    {
        return Separado::query()
            ->where('estado', 'activo')
            ->whereNotNull('vence_en')
            ->whereRaw('total - abonado > 0.009')
            ->whereDate('vence_en', '<=', today()->addDays(self::DIAS_ANTES))
            ->whereDate('vence_en', '>=', today()->subDays(self::DIAS_DESPUES));
    }

    public static function puedeVerNegocio(User $u): bool
    {
        return (bool) TiendaActual::id() && ($u->es_superadmin || $u->can('usar-separados') || $u->can('usar-pos') || $u->can('editar-negocio'));
    }

    // ['negocio' => Collection, 'asesor' => Collection, 'total' => int, 'mostrar' => bool]
    public static function paraUsuario(?User $u): array
    {
        $vacio = ['negocio' => collect(), 'asesor' => collect(), 'total' => 0, 'mostrar' => false];
        if (!$u) return $vacio;
        try {
            return Cache::remember('sep-vencer:' . $u->id . ':' . (TiendaActual::id() ?: 0), 120, function () use ($u) {
                $negocio = collect(); $asesor = collect(); $mostrar = false;
                if (self::puedeVerNegocio($u)) {
                    $mostrar = true;
                    $negocio = self::consulta()->orderBy('vence_en')->limit(30)->get()
                        ->map(fn ($s) => self::fila($s, null));
                }
                $a = $u->asesor;
                if ($a && $a->activo) {
                    $mostrar = true;
                    $ids = $a->tiendas()->pluck('id');
                    if ($ids->isNotEmpty()) {
                        $tiendas = Tienda::whereIn('id', $ids)->get(['id', 'nombre', 'telefono'])->keyBy('id');
                        $asesor = self::consulta()->withoutGlobalScope('tienda')->whereIn('tienda_id', $ids)->orderBy('vence_en')->limit(30)->get()
                            ->map(fn ($s) => self::fila($s, $tiendas[$s->tienda_id] ?? null));
                    }
                }
                return ['negocio' => $negocio, 'asesor' => $asesor, 'total' => $negocio->count() + $asesor->count(), 'mostrar' => $mostrar];
            });
        } catch (\Throwable $e) {
            report($e);
            return $vacio;
        }
    }

    public static function dias(Separado $s): int
    {
        return (int) today()->diffInDays($s->vence_en, false);
    }

    public static function texto(int $dias): string
    {
        return match (true) {
            $dias < 0 => 'Venció hace ' . abs($dias) . (abs($dias) === 1 ? ' día' : ' días'),
            $dias === 0 => 'Vence hoy',
            $dias === 1 => 'Vence mañana',
            default => "Vence en $dias días",
        };
    }

    private static function fila(Separado $s, ?Tienda $t): array
    {
        $dias = self::dias($s);
        $saldo = dinero($s->saldo);
        $fila = [
            'id' => $s->id, 'numero' => $s->numero, 'cliente' => $s->cliente_nombre, 'saldo' => $saldo,
            'dias' => $dias, 'cuando' => self::texto($dias), 'vence' => $s->vence_en->format('d/m/Y'),
            'nivel' => $dias < 0 ? 'vencido' : ($dias <= 1 ? 'urgente' : 'pronto'),
            'url' => $t ? null : route('separados.show', $s->id),
            'tienda' => $t?->nombre, 'wa' => null,
        ];
        if ($t && $t->telefono) {
            // El asesor le escribe al negocio para que le recuerde al cliente
            $fila['wa'] = 'https://wa.me/' . numero_whatsapp($t->telefono) . '?text=' . rawurlencode(
                "Hola 👋 Le escribe su asesor de LocalProo. El separado {$s->numero} de {$s->cliente_nombre} " . mb_strtolower(self::texto($dias)) . " ({$fila['vence']}) y le falta {$saldo}. ¿Le recordamos al cliente para que se acerque a pagar? En Separados tiene el botón de WhatsApp con el mensaje listo.");
        } elseif (!$t && $s->telefono_wa) {
            $fila['wa'] = 'https://wa.me/' . $s->telefono_wa . '?text=' . rawurlencode(self::mensajeCliente($s));
        }
        return $fila;
    }

    // Mensaje de WhatsApp para el cliente
    public static function mensajeCliente(Separado $s): string
    {
        $nombre = Tienda::find($s->tienda_id)?->nombre ?? 'la tienda';
        $dias = self::dias($s);
        return 'Hola ' . \Illuminate\Support\Str::words($s->cliente_nombre, 1, '') . " 👋 Su separado {$s->numero} en {$nombre} " . mb_strtolower(self::texto($dias))
            . ' (' . $s->vence_en->format('d/m/Y') . '). Le falta ' . dinero($s->saldo) . '. Acérquese a la tienda a pagar para no perder su separado. Vea sus abonos aquí: ' . separadoController::urlPublica($s);
    }

    public static function olvidar(User $u): void
    {
        Cache::forget('sep-vencer:' . $u->id . ':' . (TiendaActual::id() ?: 0));
    }
}
