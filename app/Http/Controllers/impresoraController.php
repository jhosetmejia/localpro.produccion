<?php

namespace App\Http\Controllers;

use App\Support\Impresora;
use App\Support\TiendaActual;
use Illuminate\Http\Request;

// Mi negocio → Impresora: ajustar la impresora de tiquetes para que no salga corrida ni cortada
class impresoraController extends Controller
{
    public function __construct()
    {
        // El cajero puede ajustar la impresora de SU computador; el ajuste para todo el negocio es del dueño/administrador
        $this->middleware('permission:editar-negocio|usar-pos')->except('prueba');
    }

    public function edit(Request $request)
    {
        $t = TiendaActual::tienda();
        abort_unless($t, 404);
        $para = $this->para($request, $t);
        return view('impresora.edit', [
            'tienda' => $t, 'para' => $para, 'imp' => Impresora::de($t, $para), 'configurada' => Impresora::configurada($t, $para),
            'enEquipo' => Impresora::delEquipo($t, $para) !== null, 'delNegocio' => Impresora::delNegocio($t, $para),
            'puedeNegocio' => $request->user()->can('editar-negocio'),
            'cocinaUsaCaja' => $para === 'cocina' && !Impresora::configurada($t, 'cocina') && Impresora::configurada($t, 'caja'),
        ]);
    }

    // La de cocina solo existe si el negocio usa Pedidos (restaurantes y cafeterías)
    private function para(Request $request, $t): string
    {
        $p = Impresora::para($request->input('para'));
        return $p === 'cocina' && !$t->mod_pedidos ? 'caja' : $p;
    }

    public function update(Request $request)
    {
        $t = TiendaActual::tienda();
        abort_unless($t, 404);
        $request->validate([
            'papel' => 'required|in:58,80', 'ancho' => 'required|numeric', 'margen' => 'required|numeric',
            'letra' => 'required|integer', 'final' => 'required|integer',
        ]);
        $para = $this->para($request, $t);
        $c = Impresora::normalizar($request->only('papel', 'ancho', 'margen', 'letra', 'final') + ['negrita' => $request->boolean('negrita')]);
        // Solo este computador (cookie por 5 años) o todos los del negocio
        if ($request->input('alcance') === 'equipo' || !$request->user()->can('editar-negocio')) {
            \Illuminate\Support\Facades\Cookie::queue(Impresora::cookie($t, $para), json_encode($c), 60 * 24 * 365 * 5);
            return redirect()->route('impresora.edit', ['para' => $para])->with('success', 'Guardado solo para ESTE computador: aquí la impresora ' . ($para === 'cocina' ? 'de cocina' : 'de caja') . ' sale con estos ajustes. Los otros computadores siguen con los suyos.');
        }
        $t->forceFill([Impresora::PARA[$para] => $c])->save();
        return redirect()->route('impresora.edit', ['para' => $para])->with('success', $para === 'cocina'
            ? 'Impresora de cocina guardada: las comandas ya salen con estos ajustes.'
            : 'Impresora de caja guardada: tiquetes, facturas, cuadre de caja y recibos ya salen con estos ajustes.');
    }

    // Quita los ajustes de este negocio: el tiquete vuelve a salir como venía de fábrica
    public function borrar(Request $request)
    {
        $t = TiendaActual::tienda();
        abort_unless($t, 404);
        $para = $this->para($request, $t);
        if ($request->input('alcance') === 'equipo' || !$request->user()->can('editar-negocio')) {
            \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget(Impresora::cookie($t, $para)));
            return redirect()->route('impresora.edit', ['para' => $para])->with('success', 'Listo: este computador vuelve a usar el ajuste general del negocio.');
        }
        $t->forceFill([Impresora::PARA[$para] => null])->save();
        return redirect()->route('impresora.edit', ['para' => $para])->with('success', $para === 'cocina'
            ? 'Listo: la comanda volvió a salir como venía (o con los ajustes de caja, si los tiene).'
            : 'Listo: su tiquete volvió a salir como venía (sin ajustes).');
    }

    // Tiquete de prueba con reglas para ver si sale derecho. Acepta ajustes sin guardar (para probar antes).
    public function prueba(Request $request)
    {
        $t = TiendaActual::tienda();
        $imp = $request->has('papel')
            ? Impresora::normalizar($request->only('papel', 'ancho', 'margen', 'letra', 'final') + ['negrita' => $request->boolean('negrita')])
            : Impresora::de($t, Impresora::para($request->input('para')));
        return view('impresora.prueba', ['tienda' => $t, 'impresora' => $imp, 'embed' => $request->boolean('embed'), 'cocina' => $request->input('para') === 'cocina']);
    }
}
