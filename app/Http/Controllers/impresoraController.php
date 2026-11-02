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
        $this->middleware('permission:editar-negocio')->except('prueba');
    }

    public function edit()
    {
        $t = TiendaActual::tienda();
        abort_unless($t, 404);
        return view('impresora.edit', ['tienda' => $t, 'imp' => Impresora::de($t), 'configurada' => Impresora::configurada($t)]);
    }

    public function update(Request $request)
    {
        $t = TiendaActual::tienda();
        abort_unless($t, 404);
        $request->validate([
            'papel' => 'required|in:58,80', 'ancho' => 'required|numeric', 'margen' => 'required|numeric',
            'letra' => 'required|integer', 'final' => 'required|integer',
        ]);
        $t->forceFill(['impresora' => Impresora::normalizar($request->only('papel', 'ancho', 'margen', 'letra', 'final') + ['negrita' => $request->boolean('negrita')])])->save();
        return redirect()->route('impresora.edit')->with('success', 'Impresora guardada: los tiquetes, recibos y comandas ya salen con estos ajustes.');
    }

    // Quita los ajustes de este negocio: el tiquete vuelve a salir como venía de fábrica
    public function borrar()
    {
        $t = TiendaActual::tienda();
        abort_unless($t, 404);
        $t->forceFill(['impresora' => null])->save();
        return redirect()->route('impresora.edit')->with('success', 'Listo: su tiquete volvió a salir como venía (sin ajustes).');
    }

    // Tiquete de prueba con reglas para ver si sale derecho. Acepta ajustes sin guardar (para probar antes).
    public function prueba(Request $request)
    {
        $t = TiendaActual::tienda();
        $imp = $request->has('papel')
            ? Impresora::normalizar($request->only('papel', 'ancho', 'margen', 'letra', 'final') + ['negrita' => $request->boolean('negrita')])
            : Impresora::de($t);
        return view('impresora.prueba', ['tienda' => $t, 'impresora' => $imp, 'embed' => $request->boolean('embed')]);
    }
}
