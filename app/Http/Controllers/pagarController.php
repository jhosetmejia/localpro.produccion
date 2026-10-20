<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\PagoSuscripcion;
use App\Models\Plan;
use App\Models\Tienda;
use App\Support\Wompi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// Enlace de pago de un negocio: lo abre el dueño o su asesor (sin entrar al sistema) y paga el plan con Wompi.
// El enlace va firmado, así nadie puede adivinar el de otro negocio.
class pagarController extends Controller
{
    public function show(Tienda $tienda)
    {
        return view('pagar.show', [
            'tienda' => $tienda->load('plan'),
            'planes' => Plan::where('activo', true)->where('precio', '>', 0)->orderBy('orden')->get(),
            'wompi' => Wompi::activo(),
            'soporte' => preg_replace('/\D/', '', (string) Ajuste::valor('soporte_whatsapp')),
        ]);
    }

    public function ir(Request $request, Tienda $tienda, Plan $plan)
    {
        abort_unless($plan->activo && !$plan->es_gratis, 404);
        if (!Wompi::activo()) {
            return back()->withErrors('El pago en línea aún no está disponible. Comuníquese con LocalPro.');
        }
        $pago = self::crearPago($tienda, $plan, Auth::id(), Auth::check() ? 'Pagado desde enlace por ' . Auth::user()->name : 'Pagado desde enlace de pago');
        if ((float) $pago->monto <= 0) {
            // Todo lo cubre el saldo por referidos: se activa sin pasar por Wompi
            $pago->aprobar();
            return view('pagar.retorno', ['pago' => $pago->fresh(['tienda', 'plan']), 'tienda' => $tienda]);
        }

        return redirect()->away(Wompi::urlCheckout($pago, route('pagar.retorno', ['ref' => $pago->referencia]), $tienda->email, $tienda->nombre, $tienda->telefono));
    }

    // Wompi devuelve aquí con ?id=<transacción>; se confirma el pago de una vez
    public function retorno(Request $request)
    {
        $pago = PagoSuscripcion::with(['tienda', 'plan'])->where('referencia', (string) $request->query('ref'))->firstOrFail();
        if ($pago->estado !== 'aprobado' && ($id = $request->query('id'))) {
            $tx = Wompi::transaccion((string) $id);
            if ($tx && ($tx['reference'] ?? '') === $pago->referencia) {
                $pago = Wompi::procesar($tx)?->load(['tienda', 'plan']) ?? $pago;
            }
        }
        return view('pagar.retorno', ['pago' => $pago->fresh(['tienda', 'plan']), 'tienda' => $pago->tienda]);
    }

    public static function crearPago(Tienda $tienda, Plan $plan, ?int $usuario, ?string $nota = null): PagoSuscripcion
    {
        // Venezuela paga en dólares o bolívares: el pago queda en esa moneda y Wompi cobra su equivalente en pesos (tasa de Admin → Planes)
        $moneda = $tienda->moneda_cobro;
        $monto = $plan->precioEn($moneda);
        // Saldo por referidos ("Comparta LocalProo y gane"): se descuenta solo del plan anual
        $abono = \App\Support\Referidos::descuento($tienda, $plan, $monto);
        $monto = round($monto - $abono, 2);
        if ($abono > 0) $nota = trim(($nota ?? '') . ' · Saldo por referidos: ' . \App\Support\Moneda::formato($abono, $moneda));
        return PagoSuscripcion::create([
            'tienda_id' => $tienda->id,
            'plan_id' => $plan->id,
            'abono_saldo' => $abono > 0 ? $abono : null,
            'monto' => $monto,
            'moneda' => $moneda,
            'cobro_cop' => \App\Support\Moneda::esExtranjera($moneda) ? \App\Support\Moneda::aCop($monto, $moneda) : null,
            'metodo' => $monto <= 0 ? 'saldo' : 'wompi',
            'referencia' => 'LP-' . $tienda->id . '-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(4)),
            'estado' => 'pendiente',
            'registrado_por' => $usuario,
            'nota' => $nota,
        ]);
    }
}
