<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Negocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Cada vendedor abre su caja con una base y la cierra con su cuadre
class cajaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:usar-pos', ['only' => ['abrir', 'guardarApertura', 'actual', 'cerrar', 'guardarCierre']]);
        $this->middleware('permission:ver-cajas|usar-pos', ['only' => ['index', 'show', 'reporte']]);
    }

    public function index(Request $request)
    {
        $todas = Auth::user()->can('ver-cajas-todas');
        $cajas = Caja::with('user:id,name')
            ->when(!$todas, fn ($q) => $q->where('user_id', Auth::id()))
            ->latest('abierta_en')
            ->paginate(25);
        return view('caja.index', compact('cajas', 'todas'));
    }

    public function abrir()
    {
        if (Caja::abiertaDe(Auth::id())) {
            return redirect()->route('pos.index');
        }
        $ultima = Caja::where('user_id', Auth::id())->where('estado', 'cerrada')->latest('cerrada_en')->first();
        return view('caja.abrir', compact('ultima'));
    }

    public function guardarApertura(Request $request)
    {
        $datos = $request->validate([
            'monto_inicial' => 'required|numeric|min:0|max:999999999',
            'nota_apertura' => 'nullable|string|max:255',
        ], [], ['monto_inicial' => 'base de caja']);

        DB::transaction(function () use ($datos) {
            // Evita dos cajas abiertas del mismo vendedor (doble clic)
            $existe = Caja::where('user_id', Auth::id())->where('estado', 'abierta')->lockForUpdate()->exists();
            if (!$existe) {
                Caja::create([
                    'user_id' => Auth::id(),
                    'abierta_en' => now(),
                    'monto_inicial' => $datos['monto_inicial'],
                    'nota_apertura' => $datos['nota_apertura'] ?? null,
                    'estado' => 'abierta',
                ]);
            }
        });

        return redirect()->route('pos.index')->with('success', 'Caja abierta. ¡Buenas ventas!');
    }

    public function actual()
    {
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) {
            return redirect()->route('cajas.abrir');
        }
        return redirect()->route('cajas.show', $caja);
    }

    public function show(Caja $caja)
    {
        $this->autorizar($caja);
        $caja->load('user:id,name');
        $totales = $caja->totalesPorMetodo();
        $ventas = $caja->ventas()->with('pagos.metodo')->latest()->get();
        $esperado = $caja->estado === 'abierta' ? $caja->efectivoEsperado() : (float) $caja->efectivo_esperado;
        $reembolsos = $caja->reembolsosPorMetodo();
        return view('caja.show', compact('caja', 'totales', 'ventas', 'esperado', 'reembolsos'));
    }

    public function cerrar()
    {
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) {
            return redirect()->route('cajas.abrir');
        }
        $totales = $caja->totalesPorMetodo();
        $reembolsos = $caja->reembolsosPorMetodo();
        $esperado = $caja->efectivoEsperado();
        return view('caja.cerrar', compact('caja', 'totales', 'esperado', 'reembolsos'));
    }

    public function guardarCierre(Request $request)
    {
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) {
            return redirect()->route('cajas.abrir');
        }
        $datos = $request->validate([
            'efectivo_contado' => 'required|numeric|min:0|max:999999999',
            'nota_cierre' => 'nullable|string|max:255',
        ], [], ['efectivo_contado' => 'efectivo contado']);

        DB::transaction(function () use ($caja, $datos) {
            $caja = Caja::whereKey($caja->id)->lockForUpdate()->first();
            if ($caja->estado !== 'abierta') {
                return;
            }
            $esperado = $caja->efectivoEsperado();
            $caja->update([
                'cerrada_en' => now(),
                'total_ventas' => (float) $caja->ventas()->where('estado', 'pagada')->sum('total'),
                'efectivo_esperado' => $esperado,
                'efectivo_contado' => $datos['efectivo_contado'],
                'diferencia' => (float) $datos['efectivo_contado'] - $esperado,
                'nota_cierre' => $datos['nota_cierre'] ?? null,
                'estado' => 'cerrada',
            ]);
        });

        return redirect()->route('cajas.reporte', $caja)->with('success', 'Caja cerrada.');
    }

    // Informe de cierre imprimible en 80 mm
    public function reporte(Caja $caja)
    {
        $this->autorizar($caja);
        return view('caja.reporte', [
            'caja' => $caja->load('user:id,name'),
            'totales' => $caja->totalesPorMetodo(),
            'reembolsos' => $caja->reembolsosPorMetodo(),
            'anuladas' => $caja->ventas()->where('estado', 'anulada')->count(),
            'numVentas' => $caja->ventas()->where('estado', 'pagada')->count(),
            'negocio' => Negocio::datos(),
        ]);
    }

    private function autorizar(Caja $caja): void
    {
        abort_unless((int) $caja->user_id === (int) Auth::id() || Auth::user()->can('ver-cajas-todas'), 403);
    }
}
