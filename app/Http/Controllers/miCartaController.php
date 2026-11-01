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
        $secciones = app(catalogoController::class)->seccionesCarta($t)[0];
        return view('negocio.carta', [
            'tienda' => $t,
            'listo' => (bool) ($t->catalogo_activo && $t->slug && $t->activa && $t->onboarding_completo),
            'hayProductos' => Producto::where('estado', 1)->exists(),
            'cartaIa' => ($ia = CartaIa::leer($t->id)) !== null ? CartaIa::armar($ia, $t, $secciones) : null,
            'cartaIaAuto' => $ia !== null && CartaIa::esPlantilla($ia),
            'cartaIaQr' => $ia !== null && CartaIa::enQr($t->id),
            'cartaIaFecha' => CartaIa::fecha($t->id),
            'instruccionesIa' => CartaIa::instrucciones($t, $secciones),
        ]);
    }

    // Carta para imprimir dentro del panel (con el menú), la hoja va en un marco
    public function imprimir(Request $request)
    {
        $t = TiendaActual::tienda();
        if (!($t->catalogo_activo && $t->slug && $t->activa && $t->onboarding_completo)) {
            return redirect()->route('carta.index');
        }
        $plantilla = isset(catalogoController::PLANTILLAS_CARTA[$request->query('plantilla')]) ? $request->query('plantilla') : 'pro';
        $accion = in_array($request->query('accion'), ['pdf', 'jpg'], true) ? $request->query('accion') : null;
        $src = route('catalogo.carta.imprimir', $t->slug) . '?' . http_build_query(array_filter(['plantilla' => $plantilla, 'accion' => $accion, 'embed' => 1]));
        return view('negocio.carta-imprimir', ['src' => $src]);
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
        return redirect()->route('carta.index')->with('success', CartaIa::esPlantilla($html)
            ? '¡Listo! Su carta quedó guardada y los platos y precios se actualizan solos.'
            : '¡Listo! Su carta quedó guardada. Ojo: no trae las marcas de LocalPro, así que si cambia precios tendrá que hacerla de nuevo.');
    }

    public function verIa()
    {
        $t = TiendaActual::tienda();
        $html = CartaIa::leer($t->id);
        abort_if($html === null, 404);
        return CartaIa::respuesta(CartaIa::armar($html, $t, app(catalogoController::class)->seccionesCarta($t)[0]), $t);
    }

    // Usar (o no) la carta hecha con IA en el QR de las mesas y en el enlace de la carta
    public function qrIa(Request $request)
    {
        $si = $request->boolean('en_qr');
        CartaIa::usarEnQr(TiendaActual::id(), $si);
        return redirect()->route('carta.index')->with('success', $si
            ? 'Ahora el QR de las mesas abre su carta personalizada (con el botón "Pedir en línea").'
            : 'El QR de las mesas volvió a la carta en línea normal.');
    }

    public function borrarIa()
    {
        CartaIa::borrar(TiendaActual::id());
        return redirect()->route('carta.index')->with('success', 'Carta personalizada borrada.');
    }
}
