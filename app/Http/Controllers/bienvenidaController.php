<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Tienda;
use App\Support\CrearTienda;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Asistente para la tienda nueva:
 * 2) datos del negocio y tipo · 3) primeros productos · 4) cobro y caja → POS.
 */
class bienvenidaController extends Controller
{
    // Productos de ejemplo según el tipo de negocio: [nombre, precio en COP, tallas, colores, cantidad]
    private const EJEMPLOS = [
        'moda' => [['Camiseta básica', 39900, 'S, M, L, XL', 'Negro, Blanco', 5], ['Jean clásico', 89900, '28, 30, 32, 34', 'Azul', 3], ['Buzo con capucha', 79900, 'S, M, L', 'Gris, Negro', 3], ['Vestido casual', 99900, 'S, M, L', 'Negro', 2]],
        'calzado' => [['Tenis urbanos', 159900, '37, 38, 39, 40, 41', 'Blanco, Negro', 2], ['Sandalias', 59900, '35, 36, 37, 38', 'Café', 3], ['Botas', 189900, '36, 37, 38, 39, 40', 'Negro', 2], ['Zapato formal', 169900, '39, 40, 41, 42', 'Negro', 2]],
        'accesorios' => [['Gorra', 35000, '', '', 10], ['Reloj', 89900, '', '', 5], ['Bolso', 79900, '', '', 4], ['Gafas de sol', 45000, '', '', 8]],
        'restaurante' => [['Hamburguesa clásica', 18000, '', '', 100], ['Perro caliente', 12000, '', '', 100], ['Papas a la francesa', 7000, '', '', 100], ['Gaseosa', 4000, '', '', 100]],
        'cafeteria' => [['Café americano', 4500, '', '', 100], ['Capuchino', 7000, '', '', 100], ['Pan de bono', 2500, '', '', 100], ['Torta de chocolate', 8000, '', '', 50]],
        'minimercado' => [['Arroz 1 kg', 4800, '', '', 20], ['Aceite 1 L', 12500, '', '', 20], ['Gaseosa 1.5 L', 6500, '', '', 24], ['Huevos x30', 18000, '', '', 10]],
        'belleza' => [['Shampoo', 25000, '', '', 10], ['Labial', 18000, '', '', 10], ['Crema facial', 35000, '', '', 8], ['Esmalte', 9000, '', '', 15]],
        'tecnologia' => [['Cargador USB-C', 25000, '', '', 10], ['Audífonos bluetooth', 59900, '', '', 8], ['Protector de pantalla', 15000, '', '', 20], ['Cable USB', 12000, '', '', 15]],
        'papeleria' => [['Cuaderno 100 hojas', 5500, '', '', 30], ['Lapicero', 1500, '', '', 50], ['Colores x12', 12000, '', '', 20], ['Tarjeta de regalo', 3000, '', '', 40]],
        'ferreteria' => [['Martillo', 25000, '', '', 10], ['Destornillador', 12000, '', '', 15], ['Cinta aislante', 4000, '', '', 30], ['Tornillos x100', 9000, '', '', 20]],
        'funeraria' => [['Plan exequial mensual', 25000, '', '', 0], ['Arreglo floral', 120000, '', '', 5], ['Cofre fúnebre básico', 1800000, '', '', 2], ['Servicio de cremación', 1500000, '', '', 0]],
        'otro' => [['Producto 1', 10000, '', '', 10], ['Producto 2', 15000, '', '', 10], ['Producto 3', 20000, '', '', 10], ['Producto 4', 25000, '', '', 10]],
    ];

    public function index()
    {
        $tienda = TiendaActual::tienda();
        if ($tienda->onboarding_completo) {
            return redirect()->route('panel');
        }
        abort_unless(Auth::user()->hasRole('dueno') || Auth::user()->es_superadmin, 403, 'El dueño de la tienda debe terminar la configuración.');

        $paso = max(2, min(4, (int) $tienda->onboarding_paso));
        $datos = ['tienda' => $tienda, 'paso' => $paso, 'tipos' => Tienda::TIPOS];

        if ($paso === 3) {
            $ej = self::EJEMPLOS[$tienda->tipo_negocio] ?? self::EJEMPLOS['otro'];
            $cop = $tienda->pais === 'CO';
            // Fuera de Colombia los precios de ejemplo quedan vacíos para que los ponga en su moneda
            $datos['ejemplos'] = array_map(fn ($e) => [
                'nombre' => $e[0], 'precio' => $cop ? $e[1] : '', 'tallas' => $tienda->usa_tallas ? $e[2] : '',
                'colores' => $tienda->usa_tallas ? $e[3] : '', 'cantidad' => $e[4],
            ], $ej);
        }
        if ($paso === 4) {
            $datos['metodos'] = MetodoPago::orderBy('orden')->get();
        }

        return view('bienvenida.index', $datos);
    }

    // Volver a un paso anterior para corregir
    public function irAPaso(int $paso)
    {
        $tienda = TiendaActual::tienda();
        if ($paso >= 2 && $paso < $tienda->onboarding_paso) {
            $tienda->update(['onboarding_paso' => $paso]);
        }
        return redirect()->route('bienvenida.index');
    }

    public function negocio(Request $request)
    {
        $tienda = TiendaActual::tienda();
        $datos = $request->validate([
            'nombre' => 'required|string|max:80',
            'tipo_negocio' => ['required', Rule::in(array_keys(Tienda::TIPOS))],
            'descripcion' => 'nullable|string|max:120',
            'pais' => ['required', Rule::in(array_keys(\App\Support\Paises::LISTA))],
            'telefono' => ['nullable', 'string', 'max:30', \App\Support\Validar::celular($request->input('pais'))],
            'nit' => 'nullable|string|max:30',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'mod_medida' => 'nullable|boolean',
            'mod_vencimiento' => 'nullable|boolean',
            'mod_recetas' => 'nullable|boolean',
            'mod_alquiler' => 'nullable|boolean',
            'mod_obituarios' => 'nullable|boolean',
            'mod_contable' => 'nullable|boolean',
        ] + \App\Support\Ubicaciones::reglas($request->input('pais'), $request->input('departamento')),
        ['nombre.required' => 'Escriba el nombre de su negocio.', 'tipo_negocio.required' => 'Elija el tipo de negocio.'] + \App\Support\Ubicaciones::mensajes());

        // El teléfono se guarda sin indicativo: el +57 (o el del país) lo pone el sistema
        $datos['telefono'] = \App\Support\Validar::limpiarTel($datos['telefono'] ?? null, $datos['pais']) ?: null;

        // Si cambió de país antes de vender, sus formas de pago pasan a ser las de ese país
        if ($datos['pais'] !== $tienda->pais && !\App\Models\PosVenta::exists()) {
            \App\Models\MetodoPago::query()->delete();
            foreach (CrearTienda::metodosPorPais($datos['pais']) as $i => [$nombre, $efectivo]) {
                \App\Models\MetodoPago::create(['nombre' => $nombre, 'es_efectivo' => $efectivo, 'activo' => true, 'orden' => $i + 1]);
            }
        }

        if ($request->hasFile('logo')) {
            if ($tienda->logo_path) {
                Storage::disk('public')->delete($tienda->logo_path);
            }
            $datos['logo_path'] = $request->file('logo')->store('negocio', 'public');
        }
        unset($datos['logo']);

        $datos['usa_tallas'] = Tienda::TIPOS[$datos['tipo_negocio']][2];
        foreach (['mod_medida', 'mod_vencimiento', 'mod_recetas', 'mod_alquiler', 'mod_obituarios', 'mod_contable'] as $m) {
            $datos[$m] = $request->has($m) ? $request->boolean($m) : in_array($m, Tienda::MODULOS_POR_TIPO[$datos['tipo_negocio']] ?? [], true);
        }
        if ($tienda->menu_oculto === null && isset(Tienda::MENU_OCULTO_POR_TIPO[$datos['tipo_negocio']])) {
            $datos['menu_oculto'] = Tienda::MENU_OCULTO_POR_TIPO[$datos['tipo_negocio']];
        }
        $datos['onboarding_paso'] = 3;

        // Un conjunto residencial no vende productos: termina aquí y pasa a su panel de administración
        // Un contador no vende productos: termina aquí y pasa a su módulo de contabilidad
        if ($datos['tipo_negocio'] === 'contable') {
            $datos['onboarding_paso'] = 5;
            $datos['onboarding_completo'] = true;
            $datos['mod_contable'] = true;
            $tienda->update($datos);
            return redirect()->route('contabilidad.index')->with('success', '¡Listo! Registre su primer cliente para empezar.');
        }
        if ($datos['tipo_negocio'] === 'conjunto') {
            $datos['onboarding_paso'] = 5;
            $datos['onboarding_completo'] = true;
            $tienda->update($datos);
            return redirect()->route('conjunto.unidades')->with('success', '¡Listo! Empiece creando las unidades (apartamentos o casas) del conjunto.');
        }
        $tienda->update($datos);

        return redirect()->route('bienvenida.index');
    }

    public function productos(Request $request)
    {
        $tienda = TiendaActual::tienda();

        if (!$request->boolean('omitir')) {
            $request->validate([
                'productos' => 'required|array|max:8',
                'productos.*.incluir' => 'nullable|boolean',
                'productos.*.nombre' => 'nullable|string|max:80',
                'productos.*.precio' => 'nullable|numeric|min:0|max:999999999',
                'productos.*.tallas' => 'nullable|string|max:200',
                'productos.*.colores' => 'nullable|string|max:300',
                'productos.*.cantidad' => 'nullable|integer|min:0|max:100000',
            ]);

            $elegidos = collect($request->input('productos'))
                ->filter(fn ($p) => !empty($p['incluir']) && trim($p['nombre'] ?? '') !== '');

            foreach ($elegidos as $i => $p) {
                if (($p['precio'] ?? '') === '' || $p['precio'] === null) {
                    return back()->withInput()->withErrors("Ponga el precio de \"{$p['nombre']}\" o desmárquelo.");
                }
            }

            DB::transaction(function () use ($elegidos, $tienda) {
                $categoria = CrearTienda::categoria('General');
                foreach ($elegidos as $p) {
                    $nombre = trim($p['nombre']);
                    // Si ya existe (volvió a este paso), no se duplica
                    if (Producto::where('nombre', $nombre)->exists()) {
                        continue;
                    }
                    $controla = !in_array($tienda->tipo_negocio, ['restaurante', 'cafeteria'], true)
                        && !($tienda->tipo_negocio === 'funeraria' && preg_match('/\\b(plan|servicio)\\b/iu', $nombre)); // en funerarias los planes y servicios no llevan inventario
                    $producto = Producto::create([
                        'codigo' => CrearTienda::siguienteCodigo(),
                        'nombre' => $nombre,
                        'precio_venta' => $p['precio'],
                        'controla_inventario' => $controla,
                    ]);
                    $producto->categorias()->attach($categoria->id);

                    $tallas = $tienda->usa_tallas ? \App\Support\Listas::valores($p['tallas'] ?? '', 40, 'tallas') : [];
                    $colores = $tienda->usa_tallas ? \App\Support\Listas::valores($p['colores'] ?? '', 60, 'colores') : [];
                    foreach (($tallas ?: [null]) as $t) {
                        foreach (($colores ?: [null]) as $c) {
                            $v = $producto->variantes()->create(['talla' => $t, 'color' => $c, 'stock' => 0]);
                            if ($controla && (int) ($p['cantidad'] ?? 0) > 0) {
                                $v->moverStock((int) $p['cantidad'], 'entrada', null, 'Inventario inicial');
                            }
                        }
                    }
                }
            });
        }

        $tienda->update(['onboarding_paso' => 4]);
        return redirect()->route('bienvenida.index');
    }

    public function cobro(Request $request)
    {
        $tienda = TiendaActual::tienda();
        $request->validate([
            'metodos' => 'required|array|min:1',
            'metodos.*' => 'integer',
            'nuevo_metodo' => 'nullable|string|max:40',
            'monto_inicial' => 'nullable|numeric|min:0|max:999999999',
        ], ['metodos.required' => 'Elija al menos una forma de pago.']);

        DB::transaction(function () use ($request, $tienda) {
            $activos = collect($request->input('metodos'))->map(fn ($x) => (int) $x)->all();
            MetodoPago::query()->each(fn ($m) => $m->update(['activo' => in_array($m->id, $activos, true)]));

            $nuevo = trim((string) $request->input('nuevo_metodo'));
            if ($nuevo !== '' && !MetodoPago::where('nombre', $nuevo)->exists()) {
                MetodoPago::create(['nombre' => $nuevo, 'es_efectivo' => false, 'activo' => true, 'orden' => 20]);
            }

            $tienda->update(['onboarding_paso' => 5, 'onboarding_completo' => true]);

            // Deja la caja del dueño abierta para que haga su primera venta
            if (!Caja::abiertaDe(Auth::id())) {
                Caja::create([
                    'user_id' => Auth::id(),
                    'abierta_en' => now(),
                    'monto_inicial' => (float) $request->input('monto_inicial', 0),
                    'nota_apertura' => 'Primera caja',
                    'estado' => 'abierta',
                ]);
            }
        });

        return redirect()->route('pos.index')->with('bienvenida', true);
    }

    private function lista(string $texto): array
    {
        return collect(preg_split('/[,;\n]+/', $texto))
            ->map(fn ($x) => mb_strtoupper(trim($x)))->filter()->unique()->values()->all();
    }
}
