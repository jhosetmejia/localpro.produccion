<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\MetodoPago;
use App\Models\PosVenta;
use App\Models\PosVentaItem;
use App\Models\PosVentaPago;
use App\Models\User;
use App\Models\Producto;
use App\Models\Tienda;
use App\Support\TiendaActual;

// Ejemplos para los asesores: cómo queda la tienda en línea, la página de enlaces, la factura… de negocios de muestra.
// Las tiendas de muestra se eligen en Admin → Planes y cobros (códigos LCT separados por coma).
class asesorEjemplosController extends Controller
{
    public const AJUSTE = 'ejemplos_tiendas';

    public function index()
    {
        $u = auth()->user();
        if (!$u->es_superadmin && !($u->asesor && $u->asesor->activo)) {
            return redirect()->route('panel');
        }

        $codigos = collect(explode(',', (string) Ajuste::valor(self::AJUSTE, 'LCT-00003')))
            ->map(fn ($c) => strtoupper(trim($c)))->filter()->unique()->values();

        $ejemplos = Tienda::whereIn('codigo', $codigos)->get()
            ->sortBy(fn ($t) => $codigos->search($t->codigo))
            ->map(function (Tienda $t) {
                return TiendaActual::como($t->id, function () use ($t) {
                    $listo = $t->slug && $t->onboarding_completo;
                    return [
                        'tienda' => $t,
                        'tipo' => Tienda::TIPOS[$t->tipo_negocio] ?? null,
                        'catalogo' => $listo && $t->catalogo_activo ? $t->url_catalogo_navegador : null,
                        'enlaces' => $listo && $t->enlaces_activo ? route('enlaces.publico', $t->slug) : null,
                        'productos' => Producto::enCatalogo()->count(),
                    ];
                });
            })->values();

        return view('asesores.ejemplos', ['ejemplos' => $ejemplos, 'esAdmin' => (bool) $u->es_superadmin]);
    }

    /**
     * Factura de ejemplo con datos inventados (cambian cada vez): así se ve la factura que recibe el cliente,
     * sin mostrar datos reales de ningún comprador ni negocio.
     */
    public function factura()
    {
        TiendaActual::set(null);
        $negocios = [
            ['Boutique Aurora', 'Cra 24 # 18-35 Local 3'], ['Moda Urbana Sur', 'Calle 19 # 27-10'],
            ['Calzado Andino', 'Av. Panamericana # 12-40'], ['Estilo y Tendencia', 'Cra 27 # 16-22 Local 105'],
        ];
        $ciudades = [['Pasto', 'Nariño'], ['Cali', 'Valle del Cauca'], ['Medellín', 'Antioquia'], ['Bogotá', 'Bogotá D.C.']];
        $clientes = ['Laura Gómez', 'Andrés Martínez', 'Camila Rodríguez', 'Juan Pablo Torres', 'Valentina Ruiz', 'Santiago López', 'Daniela Castro'];
        $vendedores = ['Sofía', 'Mateo', 'Isabella', 'Samuel', 'Mariana'];
        $productos = [
            ['Camiseta básica algodón', 45000, ['S', 'M', 'L'], ['Negro', 'Blanco', 'Azul']],
            ['Jean clásico tiro alto', 89000, ['8', '10', '12'], ['Azul oscuro', 'Negro']],
            ['Chaqueta impermeable', 160000, ['S', 'M', 'L'], ['Negro', 'Verde oliva']],
            ['Tenis urbanos', 129000, ['37', '38', '40', '42'], ['Blanco', 'Negro']],
            ['Gorra bordada', 35000, [null], ['Negro', 'Beige']],
            ['Buzo con capota', 99000, ['M', 'L', 'XL'], ['Gris', 'Vino']],
        ];
        [$nomNeg, $dir] = $negocios[array_rand($negocios)];
        [$ciudad, $depto] = $ciudades[array_rand($ciudades)];

        $negocio = (new \App\Models\Tienda())->forceFill([
            'nombre' => $nomNeg, 'nit' => (string) random_int(900100000, 901999999), 'direccion' => $dir,
            'ciudad' => $ciudad, 'departamento' => $depto, 'telefono' => '+57 300 000 0000',
            'regimen' => 'No responsable de IVA', 'mensaje_tiquete' => '¡Gracias por su compra!',
        ]);
        $negocio->dv = random_int(0, 9);

        $items = collect();
        foreach ((array) array_rand($productos, random_int(2, 3)) as $k) {
            [$desc, $precio, $tallas, $colores] = $productos[$k];
            $cant = random_int(1, 2);
            $items->push((new PosVentaItem())->forceFill([
                'descripcion' => $desc, 'talla' => $tallas[array_rand($tallas)], 'color' => $colores[array_rand($colores)],
                'cantidad' => $cant, 'precio' => $precio, 'subtotal' => $precio * $cant,
            ]));
        }
        $subtotal = $items->sum('subtotal');
        $descuento = random_int(0, 1) ? 10000 : 0;
        $total = $subtotal - $descuento;
        $efectivo = (bool) random_int(0, 1);
        $pagado = $efectivo ? (int) (ceil($total / 50000) * 50000) : $total;

        $pago = (new PosVentaPago())->forceFill(['monto' => $total]);
        $pago->setRelation('metodo', (new MetodoPago())->forceFill(['nombre' => $efectivo ? 'Efectivo' : 'Nequi']));

        $venta = (new PosVenta())->forceFill([
            'numero' => 'POS-' . str_pad((string) random_int(100, 4999), 6, '0', STR_PAD_LEFT), 'tipo_doc' => 'tiquete',
            'cliente_nombre' => $clientes[array_rand($clientes)], 'subtotal' => $subtotal, 'descuento' => $descuento,
            'total' => $total, 'cambio' => $pagado - $total, 'estado' => 'pagada', 'impuestos' => 0,
        ]);
        $venta->created_at = now()->subMinutes(random_int(5, 600));
        $venta->setRelation('items', $items);
        $venta->setRelation('pagos', collect([$pago]));
        $venta->setRelation('user', (new User())->forceFill(['name' => $vendedores[array_rand($vendedores)]]));
        $venta->setRelation('documento', null);
        $venta->setRelation('alquiler', null);

        return response()->view('pos.factura', ['venta' => $venta, 'negocio' => $negocio, 'ejemplo' => true])
            ->header('X-Robots-Tag', 'noindex');
    }
}
