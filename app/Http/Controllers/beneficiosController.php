<?php

namespace App\Http\Controllers;

use App\Models\PagoSuscripcion;
use App\Models\Tienda;
use App\Support\Moneda;
use App\Support\Referidos;
use App\Support\TiendaActual;
use Illuminate\Support\Facades\DB;

// "Comparta LocalProo y gane": el código del negocio, su enlace, los beneficios, el saldo y los negocios que invitó
class beneficiosController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:editar-negocio');
    }

    public function index()
    {
        $t = TiendaActual::tienda();
        $moneda = $t->moneda_cobro;
        $invitados = Tienda::where('referida_por_tienda_id', $t->id)->orderByDesc('id')->get(['id', 'nombre', 'created_at', 'plan_id', 'plan_vence', 'estado'])
            ->map(function ($i) use ($t) {
                $pagos = PagoSuscripcion::with('plan')->where('tienda_id', $i->id)->where('estado', 'aprobado')
                    ->where('referencia', 'not like', PagoSuscripcion::PREFIJO_PRUEBA . '%')->get()->filter(fn ($p) => Referidos::esAnual($p->plan));
                return ['nombre' => $i->nombre, 'desde' => $i->created_at, 'pago' => $pagos->isNotEmpty(),
                    'ganado' => (float) DB::table('referido_movimientos')->where('tienda_id', $t->id)->where('referida_id', $i->id)->where('concepto', 'abono')->sum('valor')];
            });
        return view('negocio.beneficios', [
            'tienda' => $t,
            'enlace' => Referidos::enlace($t),
            'valor' => Moneda::formato(Referidos::valor($moneda), $moneda),
            'saldo' => (float) $t->saldo_referidos,
            'saldoTxt' => Moneda::formato((float) $t->saldo_referidos, $moneda),
            'invitados' => $invitados,
            'movimientos' => DB::table('referido_movimientos')->where('tienda_id', $t->id)->orderByDesc('id')->limit(20)->get(),
            'moneda' => $moneda,
        ]);
    }
}
