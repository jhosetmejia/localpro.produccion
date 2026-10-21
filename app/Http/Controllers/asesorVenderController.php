<?php

namespace App\Http\Controllers;

use App\Models\AsesorContacto;
use App\Models\RedContacto;
use App\Models\Tienda;
use Illuminate\Http\Request;

/**
 * "Empiece a vender LocalPro": plan paso a paso para el asesor — importar su base de datos, escribir 20 mensajes
 * al día, compartir la propuesta en grupos de Facebook y WhatsApp y en sus estados, con mensajes listos y su enlace.
 */
class asesorVenderController extends Controller
{
    public const DIARIOS = 20;

    // Mensajes listos. {nombre} se cambia por el nombre del contacto; {enlace} por el enlace del asesor (con su código)
    public static function mensajes(): array
    {
        return [
            'directo' => ['titulo' => 'Mensaje por WhatsApp (uno a uno)', 'icono' => 'fa-whatsapp', 'texto' =>
                "¡Hola {nombre}! 👋\n\n¿No sabes cómo organizar tu negocio? 🤔 Con *LocalPro* lo manejas todo desde el celular:\n\n✅ Ventas y caja (cada vendedor con su caja)\n✅ Inventario por talla y color\n✅ Cuánto ganas cada día\n✅ Tu propia *tienda en línea* que sale en Google\n✅ Facturas por WhatsApp\n\n🎁 Regístrate y pruébalo *2 meses gratis* aquí 👉 {enlace}\n\nSi quieres te lo muestro en 5 minutos. ¡Feliz día! 💚"],
            'grupo' => ['titulo' => 'Publicación para grupos de Facebook y WhatsApp', 'icono' => 'fa-users', 'texto' =>
                "📢 ¿Tienes un negocio y todavía anotas en cuaderno? 📒\n\nTe presento *LocalPro*, el sistema para organizar tu negocio desde el celular:\n\n🛒 Vende con caja y tiquete por WhatsApp\n📦 Controla tu inventario (tallas, colores, existencias)\n💰 Mira cuánto vendes y cuánto ganas cada día\n👥 Cada vendedor con su usuario y su meta\n🌐 Tu tienda en línea con botón de comprar por WhatsApp\n\nSirve para tiendas de ropa, calzado, misceláneas, tiendas de barrio, restaurantes y más.\n\n🎁 *2 meses GRATIS* para probarlo. Regístrate aquí 👉 {enlace}\n\n¿Dudas? Escríbeme y te ayudo a dejarlo listo 🙌"],
            'estado' => ['titulo' => 'Para sus estados de WhatsApp', 'icono' => 'fa-circle-notch', 'texto' =>
                "¿Tienes negocio? 🏪 Organízalo desde tu celular con LocalPro: ventas, inventario, ganancias y tienda en línea 🚀\n🎁 2 meses gratis 👉 {enlace}"],
        ];
    }

    public function index(Request $request)
    {
        $yo = $request->attributes->get('asesor');
        $hoy = now()->toDateString();
        $base = AsesorContacto::where('asesor_id', $yo->id)->count();
        $hoyPropios = AsesorContacto::where('asesor_id', $yo->id)->whereDate('contactado_at', $hoy)->count();
        $hoyRed = class_exists(RedContacto::class) && \Illuminate\Support\Facades\Schema::hasTable('red_contactos')
            ? RedContacto::where('contactado_por', $yo->id)->whereDate('contactado_at', $hoy)->count() : 0;
        $semana = AsesorContacto::where('asesor_id', $yo->id)->where('contactado_at', '>=', now()->startOfWeek())->count();

        return view('asesor.vender', [
            'yo' => $yo,
            'enlace' => $yo->enlace,
            'mensajes' => self::mensajes(),
            'diarios' => self::DIARIOS,
            'base' => $base,
            'hoy' => $hoyPropios + $hoyRed,
            'semana' => $semana,
            'negocios' => Tienda::where('asesor_id', $yo->id)->count(),
            'negociosMes' => Tienda::where('asesor_id', $yo->id)->where('created_at', '>=', now()->startOfMonth())->count(),
        ]);
    }
}
