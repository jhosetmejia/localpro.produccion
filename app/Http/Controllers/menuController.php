<?php

namespace App\Http\Controllers;

use App\Models\Tienda;
use App\Support\TiendaActual;
use Illuminate\Http\Request;

// Personalizar menú: el dueño enciende o apaga las secciones que su negocio usa
class menuController extends Controller
{
    private function tienda(): Tienda
    {
        abort_unless(auth()->user()->can('editar-negocio'), 403);
        return TiendaActual::tienda();
    }

    public function edit()
    {
        $tienda = $this->tienda();
        return view('negocio.menu', [
            'tienda' => $tienda,
            'grupos' => collect(Tienda::MENU_OPCIONAL)->groupBy(fn ($x) => $x[2], true),
            'sugerido' => Tienda::MENU_OCULTO_POR_TIPO[$tienda->tipo_negocio] ?? [],
        ]);
    }

    public function update(Request $request)
    {
        $tienda = $this->tienda();
        if ($request->input('accion') === 'sugerido') {
            $ocultos = Tienda::MENU_OCULTO_POR_TIPO[$tienda->tipo_negocio] ?? [];
        } elseif ($request->input('accion') === 'todo') {
            $ocultos = [];
        } else {
            $visibles = array_keys(array_filter((array) $request->input('ver', [])));
            $ocultos = array_values(array_diff(array_keys(Tienda::MENU_OPCIONAL), $visibles));
        }
        $tienda->forceFill(['menu_oculto' => $ocultos ?: null])->save();
        return back()->with('success', 'Menú guardado. ' . ($ocultos ? count($ocultos) . ' secciones escondidas.' : 'Se ven todas las secciones.'));
    }
}
