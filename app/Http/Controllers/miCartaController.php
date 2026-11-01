<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Support\CartaIa;
use Illuminate\Http\Request;
use App\Support\TiendaActual;

// "Mi carta" (restaurantes y cafeterías): carta en línea para el QR de las mesas y carta para imprimir en PDF/JPG con 3 plantillas
class miCartaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:editar-negocio');
    }

    public function index()
    {
        $t = TiendaActual::tienda();
        return view('negocio.carta', [
            'tienda' => $t,
            'listo' => (bool) ($t->catalogo_activo && $t->slug && $t->activa && $t->onboarding_completo),
            'hayProductos' => Producto::where('estado', 1)->exists(),
            'cartaIa' => CartaIa::leer($t->id),
            'cartaIaFecha' => CartaIa::fecha($t->id),
            'instruccionesIa' => CartaIa::instrucciones($t, app(catalogoController::class)->seccionesCarta($t)[0]),
        ]);
    }

    // Guarda la carta que el negocio hizo con su IA (código HTML pegado)
    public function guardarIa(Request $request)
    {
        $request->validate(['html' => ['required', 'string', 'max:' . CartaIa::MAX_BYTES]], [
            'html.required' => 'Pegue el código HTML que le dio su IA.',
            'html.max' => 'El código es muy largo. Pídale a su IA una versión más corta (sin imágenes pegadas adentro).',
        ]);
        $html = CartaIa::limpiar((string) $request->input('html'));
        if (!preg_match('/<\s*[a-z]/i', $html) || mb_strlen(strip_tags($html)) < 20) {
            return back()->withErrors('Eso no parece el código de una carta. Copie todo lo que le respondió su IA, desde <!DOCTYPE html> hasta </html>.')->withInput();
        }
        CartaIa::guardar(TiendaActual::id(), $html);
        return redirect()->route('carta.index')->with('success', '¡Listo! Su carta personalizada quedó guardada.');
    }

    public function verIa()
    {
        $t = TiendaActual::tienda();
        $html = CartaIa::leer($t->id);
        abort_if($html === null, 404);
        return CartaIa::respuesta($html, $t);
    }

    public function borrarIa()
    {
        CartaIa::borrar(TiendaActual::id());
        return redirect()->route('carta.index')->with('success', 'Carta personalizada borrada.');
    }
}
