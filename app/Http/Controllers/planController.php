<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\PagoSuscripcion;
use App\Models\Plan;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// "Mi plan": el dueño ve su plan, cuándo vence y paga (Wompi o por WhatsApp con LocalPro)
class planController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:editar-negocio')->except('index');
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

    public function pagar(Plan $plan)
    {
        abort_unless($plan->activo && !$plan->es_gratis, 404);
        $tienda = TiendaActual::tienda();

        if (!self::wompiActivo()) {
            $tel = preg_replace('/\D/', '', (string) Ajuste::valor('soporte_whatsapp'));
            $msg = "Hola LocalPro, quiero pagar el plan {$plan->nombre} (" . '$' . number_format($plan->precio, 0, ',', '.') . " COP) para la tienda {$tienda->nombre} (#{$tienda->id}).";
            return $tel
                ? redirect()->away('https://wa.me/' . $tel . '?text=' . rawurlencode($msg))
                : back()->withErrors('El pago en línea aún no está disponible. Comuníquese con LocalPro.');
        }

        $pago = PagoSuscripcion::create([
            'tienda_id' => $tienda->id,
            'plan_id' => $plan->id,
            'monto' => $plan->precio,
            'metodo' => 'wompi',
            'referencia' => 'LP-' . $tienda->id . '-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(4)),
            'estado' => 'pendiente',
            'registrado_por' => Auth::id(),
        ]);

        $centavos = (int) round($plan->precio * 100);
        $firma = hash('sha256', $pago->referencia . $centavos . 'COP' . config('services.wompi.integrity_secret'));
        $url = 'https://checkout.wompi.co/p/?' . http_build_query([
            'public-key' => config('services.wompi.public_key'),
            'currency' => 'COP',
            'amount-in-cents' => $centavos,
            'reference' => $pago->referencia,
            'signature:integrity' => $firma,
            'redirect-url' => route('plan.retorno'),
            'customer-data:email' => Auth::user()->email,
            'customer-data:full-name' => Auth::user()->name,
        ]);

        return redirect()->away($url);
    }

    // Wompi devuelve al cliente aquí; la activación la confirma el aviso (webhook) de Wompi
    public function retorno(Request $request)
    {
        return redirect()->route('plan.index')->with('success', 'Recibimos su pago. Su plan se activa apenas Wompi lo confirme (normalmente en menos de un minuto). Recargue esta página.');
    }

    public static function wompiActivo(): bool
    {
        return (bool) (config('services.wompi.public_key') && config('services.wompi.integrity_secret') && config('services.wompi.events_secret'));
    }
}
