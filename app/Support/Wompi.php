<?php

namespace App\Support;

use App\Models\PagoSuscripcion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cobro de planes con Wompi (Web Checkout). Llaves en el .env del servidor:
 * WOMPI_PUBLIC_KEY, WOMPI_INTEGRITY_SECRET (obligatorias), WOMPI_PRIVATE_KEY y WOMPI_EVENTS_SECRET (recomendadas).
 * Llaves de pruebas (pub_test_...) usan el sandbox de Wompi.
 *
 * El plan se activa por tres caminos (el que llegue primero; nunca se aplica dos veces):
 *  1. Al volver de Wompi se consulta la transacción y, si está aprobada, se activa.
 *  2. El aviso (webhook) de Wompi a /webhooks/wompi.
 *  3. El comando wompi:conciliar (cada 10 minutos) revisa los pagos que quedaron pendientes.
 */
class Wompi
{
    public static function activo(): bool
    {
        return filled(config('services.wompi.public_key')) && filled(config('services.wompi.integrity_secret'));
    }

    public static function esPruebas(): bool
    {
        return str_starts_with((string) config('services.wompi.public_key'), 'pub_test_');
    }

    public static function apiBase(): string
    {
        return self::esPruebas() ? 'https://sandbox.wompi.co/v1' : 'https://production.wompi.co/v1';
    }

    public static function firma(string $referencia, int $centavos, string $moneda = 'COP'): string
    {
        return hash('sha256', $referencia . $centavos . $moneda . config('services.wompi.integrity_secret'));
    }

    // Wompi solo cobra en pesos: si el pago es en dólares (Venezuela) se cobra su equivalente guardado en cobro_cop
    public static function pesos(PagoSuscripcion $pago): float
    {
        return $pago->cobro_cop !== null ? (float) $pago->cobro_cop : Moneda::aCop($pago->monto, $pago->moneda ?: 'COP');
    }

    public static function centavos(PagoSuscripcion $pago): int
    {
        return (int) round(self::pesos($pago) * 100);
    }

    public static function urlCheckout(PagoSuscripcion $pago, string $redirect, ?string $email = null, ?string $nombre = null, ?string $telefono = null): string
    {
        $centavos = self::centavos($pago);
        $p = [
            'public-key' => config('services.wompi.public_key'),
            'currency' => 'COP',
            'amount-in-cents' => $centavos,
            'reference' => $pago->referencia,
            'signature:integrity' => self::firma($pago->referencia, $centavos),
            'redirect-url' => $redirect,
        ];
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && !str_ends_with($email, '.local')) $p['customer-data:email'] = $email;
        if ($nombre) $p['customer-data:full-name'] = mb_substr($nombre, 0, 60);
        $tel = preg_replace('/\D/', '', (string) $telefono);
        if (strlen($tel) === 12 && str_starts_with($tel, '57')) $tel = substr($tel, 2);
        if (strlen($tel) === 10) { $p['customer-data:phone-number'] = $tel; $p['customer-data:phone-number-prefix'] = '+57'; }

        return 'https://checkout.wompi.co/p/?' . http_build_query($p);
    }

    // Consulta pública de una transacción por su id
    public static function transaccion(string $id): ?array
    {
        try {
            $r = Http::timeout(15)->get(self::apiBase() . '/transactions/' . rawurlencode($id));
            return $r->successful() ? ($r->json('data') ?: null) : null;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    // Busca la transacción más reciente de una referencia (requiere la llave privada)
    public static function porReferencia(string $referencia): ?array
    {
        $privada = (string) config('services.wompi.private_key');
        if ($privada === '') return null;
        try {
            $r = Http::timeout(15)->withToken($privada)->get(self::apiBase() . '/transactions', ['reference' => $referencia]);
            if (!$r->successful()) return null;
            $lista = collect($r->json('data') ?: []);
            return $lista->firstWhere('status', 'APPROVED') ?: $lista->sortByDesc('created_at')->first();
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * Revisa en Wompi los pagos que siguen pendientes (últimos 3 días) y aplica los aprobados.
     * Lo usan el panel del administrador (al abrir Pagos o la ficha de una tienda) y el comando wompi:conciliar.
     * Devuelve cuántos pagos quedaron aprobados en esta revisión.
     */
    public static function verificarPendientes(?int $tiendaId = null, int $max = 25): int
    {
        if (!self::activo() || !config('services.wompi.private_key')) return 0;
        // Wompi no recibe pagos de menos de 1.500: esos enlaces nunca se pueden pagar
        PagoSuscripcion::where('metodo', 'wompi')->where('estado', 'pendiente')
            ->where(fn ($q) => $q->where(fn ($c) => $c->whereNull('cobro_cop')->where('monto', '<', 1500))->orWhere('cobro_cop', '<', 1500))
            ->when($tiendaId, fn ($q) => $q->where('tienda_id', $tiendaId))
            ->update(['estado' => 'rechazado', 'nota' => 'Enlace anulado: Wompi no recibe pagos de menos de $1.500']);
        $n = 0;
        PagoSuscripcion::where('metodo', 'wompi')->where('estado', 'pendiente')
            ->where('created_at', '>=', now()->subDays(3))
            ->when($tiendaId, fn ($q) => $q->where('tienda_id', $tiendaId))
            ->latest('id')->limit($max)->get()
            ->each(function ($pago) use (&$n) {
                if ($tx = self::porReferencia($pago->referencia)) {
                    $n += self::procesar($tx)?->estado === 'aprobado' ? 1 : 0;
                }
            });
        return $n;
    }

    // Pago de PRUEBA (valor bajo): activa el plan como uno real, sin comisiones ni contar como pago anterior
    public static function crearPagoPrueba(\App\Models\Tienda $tienda, \App\Models\Plan $plan, int $monto = 1500, ?int $usuario = null): PagoSuscripcion
    {
        return PagoSuscripcion::create([
            'tienda_id' => $tienda->id,
            'plan_id' => $plan->id,
            'monto' => max(1500, $monto), // Wompi no recibe menos de 1.500 COP (la prueba siempre va en pesos)
            'moneda' => Moneda::COP,
            'metodo' => 'wompi',
            'referencia' => PagoSuscripcion::PREFIJO_PRUEBA . $tienda->id . '-' . now()->format('ymdHis') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)),
            'estado' => 'pendiente',
            'registrado_por' => $usuario,
            'nota' => 'PAGO DE PRUEBA (sin comisiones)',
        ]);
    }

    public static function planAnual(): ?\App\Models\Plan
    {
        return \App\Models\Plan::where('activo', true)->whereBetween('dias', [360, 400])->where('precio', '>', 0)->orderBy('orden')->first();
    }

    public static function eventoValido(array $evento): bool
    {
        $secreto = (string) config('services.wompi.events_secret');
        $props = data_get($evento, 'signature.properties', []);
        $checksum = (string) data_get($evento, 'signature.checksum', '');
        if ($secreto === '' || !is_array($props) || $checksum === '') return false;
        $cadena = '';
        foreach ($props as $p) $cadena .= (string) data_get($evento, 'data.' . $p);
        $cadena .= (string) data_get($evento, 'timestamp') . $secreto;

        return hash_equals(strtolower(hash('sha256', $cadena)), strtolower($checksum));
    }

    /**
     * Aplica el resultado de una transacción de Wompi al pago con esa referencia.
     * Devuelve el pago actualizado (o null si la referencia no es de LocalPro).
     */
    public static function procesar(array $tx): ?PagoSuscripcion
    {
        $ref = (string) ($tx['reference'] ?? '');
        if ($ref === '' || !PagoSuscripcion::where('referencia', $ref)->exists()) return null;

        return DB::transaction(function () use ($tx, $ref) {
            $pago = PagoSuscripcion::where('referencia', $ref)->lockForUpdate()->first();
            if ($pago->estado === 'aprobado') return $pago;

            $estado = (string) ($tx['status'] ?? '');
            $montoOk = (int) ($tx['amount_in_cents'] ?? 0) === self::centavos($pago) && ($tx['currency'] ?? 'COP') === 'COP';
            $pago->forceFill(['respuesta' => $tx])->save();

            if ($estado === 'APPROVED' && !$montoOk) {
                Log::warning('Wompi: monto no coincide', ['ref' => $ref, 'tx' => $tx['id'] ?? null]);
                $pago->update(['estado' => 'rechazado', 'nota' => 'Monto no coincide con el plan']);
            } elseif ($estado === 'APPROVED') {
                $pago->update(['nota' => mb_substr(trim(($pago->nota ? $pago->nota . ' · ' : '') . 'Wompi ' . ($tx['payment_method_type'] ?? '') . ' ' . ($tx['id'] ?? '')), 0, 250)]);
                $pago->aprobar();
            } elseif (in_array($estado, ['DECLINED', 'VOIDED', 'ERROR'], true)) {
                $pago->update(['estado' => 'rechazado', 'nota' => 'Wompi: ' . $estado]);
            }

            return $pago->fresh();
        });
    }
}
