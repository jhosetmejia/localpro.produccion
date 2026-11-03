<?php

namespace App\Support;

use App\Models\Cliente;
use App\Models\PosVenta;
use App\Models\Producto;

/**
 * Curso corto para el dueño del negocio: tareas reales que el sistema revisa solo
 * (logo, productos con foto, tienda en línea, base de datos, contactar clientes y primera venta).
 */
class PrimerosPasos
{
    public static function pasos(): array
    {
        $t = TiendaActual::tienda();
        $conFoto = Producto::where('estado', 1)->whereNotNull('img_path')->count();
        $clientes = Cliente::count();
        $contactados = Cliente::whereNotNull('contactado_at')->count();
        $ventas = PosVenta::count();

        return [
            ['icono' => 'fa-store', 'titulo' => 'Ponga la cara de su negocio', 'hecho' => (bool) ($t?->logo_path && $t?->telefono),
                'por' => 'Su logo y su WhatsApp salen en los tiquetes, en la tienda en línea y en cada mensaje. Así lo reconocen y le escriben.',
                'como' => 'En Datos del negocio suba su logo y escriba el WhatsApp del negocio.', 'boton' => 'Ir a Datos del negocio', 'url' => route('negocio.edit'),
                'avance' => $t?->logo_path ? ($t?->telefono ? 'Listo' : 'Falta el WhatsApp') : 'Falta el logo'],
            ['icono' => 'fa-shirt', 'titulo' => 'Suba 3 productos con foto', 'hecho' => $conFoto >= 3,
                'por' => 'Lo que tiene foto se vende más. Con sus productos cargados ya puede vender en la caja y mostrarlos en su tienda en línea.',
                'como' => 'En Productos → Nuevo producto: nombre, precio, foto y cantidades por talla y color. También puede importarlos desde Excel.', 'boton' => 'Crear un producto', 'url' => route('productos.create'),
                'avance' => min($conFoto, 3) . ' de 3 con foto'],
            ['icono' => 'fa-globe', 'titulo' => 'Active su tienda en línea', 'hecho' => (bool) $t?->catalogo_activo,
                'por' => 'Sus clientes ven sus productos y le compran por WhatsApp a cualquier hora. Además aparece en Google.',
                'como' => 'En Datos del negocio active "Tienda en línea visible al público" y comparta el enlace en sus redes.', 'boton' => 'Activar mi tienda', 'url' => route('negocio.edit'),
                'avance' => $t?->catalogo_activo ? 'Activa' : 'Apagada'],
            ['icono' => 'fa-address-book', 'titulo' => 'Cree su base de datos de clientes', 'hecho' => $clientes >= 10,
                'por' => 'Sus contactos del celular son sus primeros clientes. Con ellos guardados, la venta queda a su nombre y les puede escribir con un toque.',
                'como' => 'En Clientes → "Crear base de datos": exporte los contactos de su celular y súbalos. No se repiten.', 'boton' => 'Subir mis contactos', 'url' => route('clientes.contactos'),
                'avance' => number_format(min($clientes, 10), 0, ',', '.') . ' de 10 clientes'],
            ['icono' => 'fa-whatsapp', 'brand' => true, 'titulo' => 'Escríbale a 5 clientes nuevos', 'hecho' => $contactados >= 5,
                'por' => 'Cada día a usted y a cada vendedor les salen 20 contactos con un mensaje lindo y el enlace de su tienda. Es la forma más fácil de vender más.',
                'como' => 'En "Contactar clientes" toque Enviar en cada contacto: se abre WhatsApp con el mensaje listo.', 'boton' => 'Contactar clientes', 'url' => route('clientes.contactar'),
                'avance' => min($contactados, 5) . ' de 5 contactados'],
            ['icono' => 'fa-cash-register', 'titulo' => 'Haga su primera venta', 'hecho' => $ventas >= 1,
                'por' => 'Con cada venta el inventario se descuenta solo y usted sabe cuánto vendió y cuánto ganó.',
                'como' => 'Abra su caja, entre a Vender (POS), busque o escanee el producto con la cámara y cobre.', 'boton' => 'Ir a vender', 'url' => route('pos.index'),
                'avance' => $ventas >= 1 ? '¡Ya vendió!' : 'Aún sin ventas'],
        ];
    }

    public static function resumen(): array
    {
        $p = self::pasos();
        $h = count(array_filter($p, fn ($x) => $x['hecho']));
        return ['hechos' => $h, 'total' => count($p)];
    }

    /**
     * Guía con foco (como CapCut) de cada paso: qué iluminar en la página a la que lleva el botón.
     * Si un campo no aparece (otro diseño, permisos), el globo sale en el centro con la explicación.
     */
    public static function foco(int $i): array
    {
        $guardar = ['sel' => 'form button[type=submit]:not([form]), form .btn-primary[type=submit]', 'titulo' => 'Guarde', 'texto' => 'Cuando termine, toque este botón para guardar. ¡Y listo este paso!'];
        $f = [
            0 => [
                ['sel' => 'input[name=logo]', 'titulo' => 'Su logo', 'texto' => 'Toque aquí y suba el logo de su negocio (una foto sirve).'],
                ['sel' => 'input[name=telefono]', 'titulo' => 'Su WhatsApp', 'texto' => 'Escriba el número de WhatsApp del negocio: así le escriben los clientes.'],
                $guardar,
            ],
            1 => [
                ['sel' => 'input[name=nombre]', 'titulo' => 'Nombre del producto', 'texto' => 'Escriba el nombre como lo buscaría un cliente. Ej: "Chaqueta de cuero café para dama".'],
                ['sel' => 'input[name=precio_venta]', 'titulo' => 'Precio', 'texto' => 'El precio al que lo vende.'],
                ['sel' => 'input[name=img_path]', 'titulo' => 'La foto', 'texto' => 'Lo que tiene foto se vende más: tómela con el celular y súbala aquí.'],
                $guardar,
            ],
            2 => [
                ['sel' => 'input[name=catalogo_activo][type=checkbox]', 'titulo' => 'Su tienda en línea', 'texto' => 'Active esta opción para que su tienda salga en internet y en Google.'],
                $guardar,
            ],
            3 => [
                ['sel' => 'input[type=file]', 'titulo' => 'Sus contactos', 'texto' => 'Exporte los contactos de su celular y súbalos aquí. No se repiten.'],
            ],
            4 => [
                ['sel' => 'a[href*="wa.me"], a[href*="whatsapp"], .btn-success', 'titulo' => 'Escríbales', 'texto' => 'Toque Enviar en cada contacto: se abre WhatsApp con el mensaje listo.'],
            ],
            5 => [
                ['sel' => 'input[type=search], input[name=q], input[placeholder*="uscar"]', 'titulo' => 'Busque el producto', 'texto' => 'Escriba el nombre o escanee el código con la cámara, agréguelo y cobre.'],
            ],
        ];
        $paso = self::pasos()[$i] ?? null;
        if (!$paso) return [];
        // Siempre arranca con la explicación del paso
        return array_merge([['sel' => null, 'titulo' => ($i + 1) . '. ' . $paso['titulo'], 'texto' => $paso['como']]], $f[$i] ?? []);
    }
}
