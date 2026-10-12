<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\PosVenta;
use App\Models\Producto;
use App\Models\RedCuenta;
use App\Models\User;

// Tareas por niveles del panel del empresario (con estrellas y certificado)
class Niveles
{
    // Cada paso: [titulo, subtitulo, hecho, url, boton, icono, porcentaje, url2, boton2, nuevaPestaña]
    public static function calcular(): array
    {
        $t = TiendaActual::tienda();
        $tid = (int) $t?->id;
        $nProd = Producto::count();
        $nCli = Cliente::count();
        $compartio = (bool) Ajuste::valor('t' . $tid . '_compartio');
        $nRedes = $tid ? RedCuenta::where('tienda_id', $tid)->count() : 0;
        $nUsuarios = $tid ? User::where('tienda_id', $tid)->count() : 0;
        $nVentas = $tid ? PosVenta::where('tienda_id', $tid)->where('estado', 'pagada')->count() : 0;
        $tieneLogo = filled($t?->logo_path);
        // Cajas cerradas de verdad (no cuentan las de ventas históricas importadas)
        $nCierres = $tid ? Caja::where('tienda_id', $tid)->where('estado', 'cerrada')->where(fn ($q) => $q->whereNull('nota_apertura')->orWhere('nota_apertura', 'not like', 'Ventas históricas%'))->count() : 0;

        $n1 = [
            ['Crea tus primeros 10 productos', min($nProd, 10) . ' de 10 listos', $nProd >= 10, route('productos.create'), 'Crear producto', 'fa-box-open', min(100, $nProd * 10), route('productos.importar'), '¿Los tiene en Excel?', false],
            ['Comparte tu tienda en línea con tus contactos', 'Envía tu página por WhatsApp a tus clientes y grupos', $compartio, route('panel.compartir'), 'Compartir por WhatsApp', 'fa-share-nodes', 0, null, null, true],
            ['Mete tus contactos al sistema', min($nCli, 10) . ' de 10 clientes · para enviarles mensajes por WhatsApp', $nCli >= 10, route('clientes.create'), 'Agregar cliente', 'fa-address-book', min(100, $nCli * 10), route('clientes.importar'), '¿Los tiene en Excel?', false],
            ['Haz tu primera venta en la caja', $nVentas > 0 ? $nVentas . ' ventas registradas' : 'Abra la caja y registre una venta: el inventario se descuenta solo', $nVentas > 0, route('cajas.abrir'), 'Abrir caja y vender', 'fa-cash-register', 0, null, null, false],
            ['Conecta tus redes sociales', 'Facebook e Instagram: responda todos sus chats y publique desde LocalPro', $nRedes > 0, route('redes.cuentas'), 'Conectar redes', 'fa-share-from-square', 0, null, null, false],
        ];
        $n2 = [
            ['Crea un usuario para tu vendedor o asesor', $nUsuarios > 1 ? 'Ya tiene ' . ($nUsuarios - 1) . ' en su equipo' : 'Cada uno entra con su clave y vende en su propia caja (no ve sus ganancias)', $nUsuarios > 1, route('users.create'), 'Crear usuario', 'fa-user-plus', 0, null, null, false],
            ['Pon el logo de tu negocio', 'Sale en sus tiquetes, facturas y en su tienda en línea', $tieneLogo, route('negocio.edit'), 'Subir logo', 'fa-image', 0, null, null, false],
            ['Cierra tu caja al final del día', $nCierres > 0 ? $nCierres . ' cierres hechos' : 'Cuente el efectivo y LocalPro le dice si le sobra o le falta plata', $nCierres > 0, route('cajas.cerrar'), 'Cerrar caja', 'fa-lock', 0, null, null, false],
        ];
        $h1 = collect($n1)->where(2, true)->count();
        $h2 = collect($n2)->where(2, true)->count();
        $nivel = $h1 < count($n1) ? 1 : ($h2 < count($n2) ? 2 : 3);

        return [
            'tienda' => $t, 'tid' => $tid, 'nivel' => $nivel,
            'pasos' => $nivel === 1 ? $n1 : $n2,
            'hechos' => $nivel === 1 ? $h1 : $h2,
            'total' => $nivel === 1 ? count($n1) : count($n2),
            'logrados' => $nivel - 1, // niveles ya completados
        ];
    }

    public static function nombre(int $n): string
    {
        return [1 => 'Nivel 1 · Competencias básicas en el manejo de LocalPro', 2 => 'Nivel 2 · Negocio profesional'][$n] ?? '';
    }
}
