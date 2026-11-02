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
            'cartaIaCompartir' => $ia !== null ? CartaIa::compartido($t->id) : null,
            'disenosPublicos' => CartaIa::publicas($t->id),
        ]);
    }

    // QR para las mesas, listo para imprimir: logo, QR a la carta en línea y "Hecho con LocalProo" pequeño
    public function qr()
    {
        $t = TiendaActual::tienda();
        if (!($t->catalogo_activo && $t->slug && $t->activa && $t->onboarding_completo)) {
            return redirect()->route('carta.index')->withErrors('Primero active su tienda en línea en Datos del negocio para tener el enlace de la carta.');
        }
        return view('negocio.carta-qr', ['tienda' => $t, 'url' => $t->url_carta]);
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
        // Se le preguntó si quiere que el diseño sea público (solo se puede si trae las marcas: así no se ven sus precios)
        $publica = CartaIa::esPlantilla($html) && $request->input('compartir') === 'si';
        CartaIa::compartir(TiendaActual::id(), $request->has('compartir') ? $publica : null);
        return redirect()->route('carta.index')->with('success', CartaIa::esPlantilla($html)
            ? '¡Listo! Su carta quedó guardada y los platos y precios se actualizan solos.' . ($publica ? ' Su diseño ya aparece para que otros negocios lo usen.' : '')
            : '¡Listo! Su carta quedó guardada. Ojo: no trae las marcas de LocalPro, así que si cambia precios tendrá que hacerla de nuevo (y no se puede compartir con otros negocios).');
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

    // Respuesta a "¿Quiere que su diseño sea público?" (para las cartas que ya estaban guardadas)
    public function compartirIa(Request $request)
    {
        $html = CartaIa::leer(TiendaActual::id());
        abort_if($html === null, 404);
        $si = $request->input('compartir') === 'si' && CartaIa::esPlantilla($html);
        CartaIa::compartir(TiendaActual::id(), $si);
        return redirect()->route('carta.index')->with('success', $si
            ? '¡Gracias! Su diseño quedó público: otros negocios lo pueden usar con sus propios platos y precios.'
            : 'Listo, su diseño es solo suyo.');
    }

    // Vista previa de un diseño público con los platos y precios de ESTE negocio
    public function verPublica(int $id)
    {
        $t = TiendaActual::tienda();
        $html = CartaIa::publica($id);
        abort_if($html === null, 404);
        return CartaIa::respuesta(CartaIa::armar($html, $t, app(catalogoController::class)->seccionesCarta($t)[0]), $t);
    }

    // Usar un diseño que otro negocio compartió (reemplaza la carta personalizada propia)
    public function usarPublica(int $id)
    {
        if (!CartaIa::usarPublica($id, TiendaActual::id())) {
            return redirect()->route('carta.index')->withErrors('Ese diseño ya no está disponible.');
        }
        return redirect()->route('carta.index')->with('success', '¡Listo! Ya tiene ese diseño con sus platos y precios. Si quiere usarlo en el QR de las mesas, active el interruptor.');
    }

    // Solo el súper administrador: saca un diseño de la galería (contenido inapropiado). El negocio conserva su carta.
    public function ocultarPublica(int $id)
    {
        abort_unless((bool) optional(auth()->user())->es_superadmin, 403);
        \Illuminate\Support\Facades\DB::table('cartas_ia')->where('id', $id)->update(['oculta' => true]);
        return redirect()->route('carta.index')->with('success', 'Diseño ocultado de la galería.');
    }

    public function borrarIa()
    {
        CartaIa::borrar(TiendaActual::id());
        return redirect()->route('carta.index')->with('success', 'Carta personalizada borrada.');
    }
}
