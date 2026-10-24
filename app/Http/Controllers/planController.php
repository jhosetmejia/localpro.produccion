<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\PagoSuscripcion;
use App\Models\Plan;
use App\Support\TiendaActual;
use App\Support\Wompi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// "Mi plan": el dueño ve su plan, cuándo vence y paga (Wompi o por WhatsApp con LocalPro)
class planController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:editar-negocio')->except('index', 'vencido');
    }

    public function index()
    {
        $tienda = TiendaActual::tienda()->load('plan');
        return view('plan.index', [
            'tienda' => $tienda,
            'planes' => Plan::where('activo', true)->orderBy('orden')->get(),
            'pagos' => PagoSuscripcion::with('plan')->where('tienda_id', $tienda->id)->latest()->limit(12)->get(),
            'wompi' => self::wompiActivo(),
            'soporte' => Ajuste::valor('soporte_whatsapp'),
        ]);
    }

    // Pantalla completa al terminar la prueba (o vencer el plan): pagar mensual o anual, y fecha en que se borra la información
    public function vencido(Request $request)
    {
        $tienda = TiendaActual::tienda()->load('plan');
        if (!$tienda->plan_vencido) {
            return redirect()->route('panel');
        }
        $user = Auth::user();
        $puedePagar = $user->can('editar-negocio');
        if ($puedePagar) {
            \App\Support\AvisosPlan::registrarVisto($tienda, $user, $request->ip());
        }
        $esPrueba = \App\Support\AvisosPlan::esPrueba($tienda);
        return view('plan.vencido', [
            'tienda' => $tienda,
            'planes' => Plan::where('activo', true)->where('precio', '>', 0)->orderBy('orden')->get(),
            'esPrueba' => $esPrueba,
            'fechaBorrado' => $tienda->datos_depurados ? null : \App\Support\AvisosPlan::fechaBorrado($tienda),
            'puedePagar' => $puedePagar,
            'wompi' => self::wompiActivo(),
            'soporte' => preg_replace('/\D/', '', (string) Ajuste::valor('soporte_whatsapp')),
        ]);
    }

    public function pagar(Request $request, Plan $plan)
    {
        abort_unless($plan->activo && !$plan->es_gratis, 404);
        $tienda = TiendaActual::tienda();
        $donar = \App\Support\Donacion::activa() && $request->boolean('donar') ? \App\Support\Donacion::monto() : 0;

        if (!self::wompiActivo()) {
            $tel = preg_replace('/\D/', '', (string) Ajuste::valor('soporte_whatsapp'));
            $m = $tienda->moneda_cobro;
            $msg = "Hola LocalPro, quiero pagar el plan {$plan->nombre} (" . \App\Support\Moneda::formato($plan->precioEn($m), $m, true) . ") para la tienda {$tienda->nombre} (#{$tienda->id}).";
            if ($donar) $msg .= ' Y quiero donar ' . \App\Support\Moneda::formato($donar, 'COP', true) . ' para los niños en diciembre.';
            return $tel
                ? redirect()->away('https://wa.me/' . $tel . '?text=' . rawurlencode($msg))
                : back()->withErrors('El pago en línea aún no está disponible. Comuníquese con LocalPro.');
        }

        $pago = pagarController::crearPago($tienda, $plan, Auth::id(), 'Pagado por ' . Auth::user()->name . ' desde Mi plan', $donar);
        if ((float) $pago->monto <= 0) {
            // Todo lo cubre el saldo por referidos: se activa sin pasar por Wompi
            $pago->aprobar();
            return redirect()->route('plan.index')->with('success', '🎁 ¡Su plan ' . $plan->nombre . ' quedó activo con su saldo por referidos! Vence el ' . optional($pago->fresh()->periodo_hasta)->format('d/m/Y') . '.');
        }
        $url = Wompi::urlCheckout($pago, route('plan.retorno', ['ref' => $pago->referencia]), Auth::user()->email, Auth::user()->name, $tienda->telefono);

        return redirect()->away($url);
    }

    // Wompi devuelve al cliente aquí con ?id=<transacción>: se confirma de una vez (el aviso de Wompi y la conciliación son respaldo)
    public function retorno(Request $request)
    {
        $tienda = TiendaActual::tienda();
        $pago = PagoSuscripcion::where('tienda_id', $tienda->id)->where('referencia', (string) $request->query('ref'))->first();
        if ($pago && $pago->estado !== 'aprobado' && ($id = $request->query('id'))) {
            $tx = Wompi::transaccion((string) $id);
            if ($tx && ($tx['reference'] ?? '') === $pago->referencia) {
                $pago = Wompi::procesar($tx) ?? $pago;
            }
        }
        $r = redirect()->route('plan.index');
        return match ($pago?->estado) {
            'aprobado' => $r->with('success', '¡Pago aprobado! Su plan ' . ($pago->plan->nombre ?? '') . ' quedó activo hasta el ' . optional($pago->periodo_hasta)->format('d/m/Y') . '.' . ($pago->donacion ? ' 🎁 ¡Gracias por su donación para los niños de Pasto! Lo invitaremos a la entrega en diciembre.' : '')),
            'rechazado' => $r->withErrors('El pago no fue aprobado. Puede intentarlo de nuevo con otro medio de pago.'),
            default => $r->with('success', 'Su pago está en proceso. El plan se activa apenas Wompi lo confirme (normalmente en pocos minutos).'),
        };
    }

    public static function wompiActivo(): bool
    {
        return Wompi::activo();
    }
}
