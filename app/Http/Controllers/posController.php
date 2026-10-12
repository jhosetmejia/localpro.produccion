<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\Negocio;
use App\Models\PosVenta;
use App\Models\ProductoVariante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class posController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('facturaPublica');
        $this->middleware('permission:usar-pos', ['only' => ['index', 'buscar', 'store', 'clientes', 'crearCliente']]);
        $this->middleware('permission:usar-pos|ver-cajas', ['only' => ['ticket', 'ventas']]);
        $this->middleware('permission:anular-venta-pos', ['only' => ['anular']]);
    }

    public function index()
    {
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) {
            return redirect()->route('cajas.abrir')->with('success', 'Abra su caja para empezar a vender.');
        }
        return view('pos.index', [
            'caja' => $caja,
            'metodos' => MetodoPago::activos()->get(['id', 'nombre', 'es_efectivo']),
            'negocio' => Negocio::datos(),
        ]);
    }

    // Busca prendas por nombre, código del producto o código de barras de la talla/color
    public function buscar(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $variantes = ProductoVariante::query()
            ->with('producto:id,nombre,codigo,precio_venta,precio_costo,img_path,controla_inventario,unidad')
            ->where('estado', 1)
            ->whereHas('producto', fn ($p) => $p->where('estado', 1))
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('codigo_barras', $q)
                        ->orWhereHas('producto', fn ($p) => $p->where('nombre', 'like', "%$q%")->orWhere('codigo', 'like', "%$q%"));
                });
            })
            ->orderByDesc('stock')
            ->limit(120)
            ->get();

        // El costo solo lo ve quien registra entradas (no los vendedores)
        $verCosto = Auth::user()->can('ver-entradas');

        // Agrupadas por producto para elegir talla y color en el POS
        $productos = $variantes->groupBy('producto_id')->map(function ($grupo) use ($verCosto) {
            $p = $grupo->first()->producto;
            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'codigo' => $p->codigo,
                'precio' => (float) $p->precio_venta,
                'img' => $p->img_path ? asset('storage/productos/' . $p->img_path) : null,
                'stock' => round((float) $grupo->sum('stock'), 3),
                'controla' => (bool) $p->controla_inventario,
                'unidad' => $p->unidad ?: 'und',
                'unidad_txt' => $p->unidad_corta,
                'unidad_pl' => \App\Models\Producto::UNIDADES[$p->unidad ?: 'und'][2] ?? 'unidades',
                'fraccion' => $p->es_fraccion,
                'variantes' => $grupo->sortBy(['talla', 'color'])->values()->map(fn ($v) => [
                    'id' => $v->id,
                    'talla' => $v->talla,
                    'color' => $v->color,
                    'etiqueta' => $v->etiqueta,
                    'stock' => round((float) $v->stock, 3),
                    'precio' => $v->precio_final,
                    'codigo_barras' => $v->codigo_barras,
                    'costo' => $verCosto ? $v->costo_final : null,
                ]),
            ];
        })->values();

        // Si escanearon un código de barras exacto, el POS lo agrega directo
        $exacta = $q !== '' ? $variantes->firstWhere('codigo_barras', $q) : null;

        return response()->json([
            'productos' => $productos,
            'exacta' => $exacta ? $exacta->id : null,
        ]);
    }

    public function store(Request $request)
    {
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) {
            return response()->json(['message' => 'Su caja está cerrada. Ábrala para vender.'], 422);
        }

        $datos = $request->validate([
            'items' => 'required|array|min:1|max:200',
            'items.*.variante_id' => 'required|integer|exists:producto_variantes,id',
            'items.*.cantidad' => 'required|numeric|min:0.001|max:100000',
            'descuento' => 'nullable|numeric|min:0|max:999999999',
            'pagos' => 'required|array|min:1|max:6',
            'pagos.*.metodo_id' => 'required|integer|exists:metodos_pago,id',
            'pagos.*.monto' => 'required|numeric|min:0|max:999999999',
            'pagos.*.referencia' => 'nullable|string|max:60',
            'cliente_id' => 'nullable|integer',
            'cliente_nombre' => 'nullable|string|max:80',
            'cliente_telefono' => 'nullable|string|max:20',
        ], [
            'items.required' => 'Agregue al menos una prenda.',
            'pagos.required' => 'Indique cómo pagó el cliente.',
        ]);

        // Si la tienda lo exige, no se vende sin cliente
        if (\App\Support\TiendaActual::tienda()->cliente_obligatorio
            && empty($datos['cliente_id'])
            && !(!empty($datos['cliente_nombre']) && !empty($datos['cliente_telefono']))) {
            throw ValidationException::withMessages(['cliente_id' => 'Esta tienda pide el cliente para cobrar. Búsquelo o créelo con el botón "Nuevo".']);
        }

        $venta = DB::transaction(function () use ($datos, $caja) {
            // Bloquea las tallas/colores vendidos para que dos cajas no vendan la misma unidad
            $ids = collect($datos['items'])->pluck('variante_id')->unique();
            $variantes = ProductoVariante::with('producto:id,nombre,precio_venta,precio_costo,controla_inventario,unidad')
                ->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

            if ($variantes->count() !== $ids->count()) {
                throw ValidationException::withMessages(['items' => 'Algún producto ya no está disponible. Recargue el POS.']);
            }

            $pedidas = collect($datos['items'])->groupBy('variante_id')->map(fn ($g) => round($g->sum(fn ($i) => (float) $i['cantidad']), 3));
            foreach ($pedidas as $id => $cant) {
                $v = $variantes[$id];
                // Lo que se vende por unidad va en números enteros; por metro/kilo/litro acepta decimales
                if (!$v->producto->es_fraccion && floor($cant) != $cant) {
                    throw ValidationException::withMessages(['items' => "{$v->producto->nombre} se vende por unidades enteras."]);
                }
                if ($v->controla && (float) $v->stock + 0.0005 < $cant) {
                    throw ValidationException::withMessages([
                        'items' => "No hay suficiente {$v->producto->nombre} ({$v->etiqueta}). Disponibles: " . cantidad($v->stock, $v->producto->unidad ?: 'und') . '.',
                    ]);
                }
            }

            // Precio oficial de cada talla/color (no se confía en el que envía el navegador)
            $items = collect($datos['items'])->map(fn ($i) => array_merge($i, ['precio' => $variantes[$i['variante_id']]->precio_final]));
            $subtotal = $items->sum(fn ($i) => round($i['cantidad'] * $i['precio'], 2));
            $descuento = min((float) ($datos['descuento'] ?? 0), $subtotal);
            $total = round($subtotal - $descuento, 2);

            $metodos = MetodoPago::where('activo', true)->whereIn('id', collect($datos['pagos'])->pluck('metodo_id'))->get()->keyBy('id');
            if ($metodos->count() !== collect($datos['pagos'])->pluck('metodo_id')->unique()->count()) {
                throw ValidationException::withMessages(['pagos' => 'Algún método de pago no está disponible. Recargue el POS.']);
            }
            $pagos = collect($datos['pagos'])->filter(fn ($p) => $p['monto'] > 0)->values();
            $pagado = round($pagos->sum('monto'), 2);
            if ($pagado + 0.001 < $total) {
                throw ValidationException::withMessages(['pagos' => 'El pago no alcanza: faltan ' . dinero($total - $pagado)]);
            }
            // Solo el efectivo puede dar cambio
            $cambio = round($pagado - $total, 2);
            $efectivoRecibido = $pagos->filter(fn ($p) => $metodos[$p['metodo_id']]->es_efectivo)->sum('monto');
            if ($cambio > 0 && $cambio - $efectivoRecibido > 0.001) {
                throw ValidationException::withMessages(['pagos' => 'Los pagos que no son en efectivo no pueden superar el total.']);
            }

            // Pagos con saldo a favor (nota crédito de un cambio): se valida y se descuenta el saldo
            foreach ($pagos as $p) {
                if (!$metodos[$p['metodo_id']]->es_credito) {
                    continue;
                }
                $nc = \App\Models\Devolucion::where('codigo', strtoupper(trim((string) ($p['referencia'] ?? ''))))
                    ->where('tipo', 'cambio')->lockForUpdate()->first();
                if (!$nc || $nc->saldo_disponible + 0.001 < $p['monto']) {
                    throw ValidationException::withMessages(['pagos' => 'La nota crédito no tiene saldo suficiente.']);
                }
                $nc->decrement('saldo_disponible', $p['monto']);
            }

            // Cliente: el elegido en el buscador, o uno nuevo si escribieron nombre y teléfono
            $clienteId = null;
            if (!empty($datos['cliente_id'])) {
                $clienteId = \App\Models\Cliente::whereKey($datos['cliente_id'])->value('id');
            } elseif (!empty($datos['cliente_nombre']) && !empty($datos['cliente_telefono'])) {
                $tel = preg_replace('/\D/', '', $datos['cliente_telefono']);
                $existente = \App\Models\Persona::where('telefono', $tel)->whereHas('cliente')->first();
                if ($existente) {
                    $clienteId = $existente->cliente->id;
                } else {
                    $persona = \App\Models\Persona::create([
                        'razon_social' => $datos['cliente_nombre'],
                        'direccion' => '',
                        'telefono' => $tel,
                        'tipo_persona' => 'natural',
                        'documento_id' => \App\Models\Documento::orderByRaw("tipo_documento = 'Cédula de ciudadanía' desc")->value('id'),
                        'numero_documento' => 'TEL-' . $tel,
                    ]);
                    $clienteId = $persona->cliente()->create(['persona_id' => $persona->id])->id;
                }
            }

            $venta = PosVenta::create([
                'numero' => \App\Support\TiendaActual::tienda()->siguienteNumeroPos(),
                'caja_id' => $caja->id,
                'user_id' => Auth::id(),
                'cliente_id' => $clienteId,
                'cliente_nombre' => $datos['cliente_nombre'] ?? null,
                'cliente_telefono' => $datos['cliente_telefono'] ?? null,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'cambio' => max($cambio, 0),
                'estado' => 'pagada',
            ]);

            foreach ($items as $i) {
                $v = $variantes[$i['variante_id']];
                $venta->items()->create([
                    'producto_variante_id' => $v->id,
                    'descripcion' => $v->producto->nombre,
                    'talla' => $v->talla,
                    'color' => $v->color,
                    'cantidad' => $i['cantidad'],
                    'precio' => $i['precio'],
                    'costo' => $v->costo_final,
                    'subtotal' => round($i['cantidad'] * $i['precio'], 2),
                ]);
                if ($v->controla) {
                    $v->moverStock(-round((float) $i['cantidad'], 3), 'venta', $venta->numero);
                }
            }

            // El cambio se descuenta del efectivo para que la caja cuadre
            $cambioPendiente = max($cambio, 0);
            foreach ($pagos as $p) {
                $monto = (float) $p['monto'];
                if ($cambioPendiente > 0 && $metodos[$p['metodo_id']]->es_efectivo) {
                    $resta = min($cambioPendiente, $monto);
                    $monto -= $resta;
                    $cambioPendiente -= $resta;
                }
                if ($monto > 0) {
                    $venta->pagos()->create([
                        'metodo_pago_id' => $p['metodo_id'],
                        'monto' => round($monto, 2),
                        'referencia' => $p['referencia'] ?? null,
                    ]);
                }
            }

            return $venta;
        });

        return response()->json([
            'id' => $venta->id,
            'numero' => $venta->numero,
            'total' => (float) $venta->total,
            'cambio' => (float) $venta->cambio,
            'ticket_url' => route('pos.ticket', $venta),
            'factura_url' => $venta->url_factura,
            'whatsapp' => $venta->whatsapp_numero,
        ]);
    }

    // Busca clientes de la tienda por nombre, teléfono o documento
    public function clientes(Request $request)
    {
        $q = trim((string) $request->get('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }
        $lista = \App\Models\Cliente::with('persona')
            ->whereHas('persona', fn ($p) => $p->where(fn ($w) => $w->where('razon_social', 'like', "%$q%")->orWhere('telefono', 'like', "%$q%")->orWhere('numero_documento', 'like', "%$q%")))
            ->limit(8)->get()
            ->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->persona->razon_social, 'telefono' => $c->persona->telefono, 'documento' => $c->persona->numero_documento]);
        return response()->json($lista);
    }

    // Crea un cliente desde la caja (nombre, cédula, WhatsApp y dirección)
    public function crearCliente(Request $request)
    {
        $d = $request->validate([
            'nombre' => 'required|string|min:2|max:80',
            'documento' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:80',
        ], [
            'nombre.required' => 'Escriba el nombre del cliente.',
            'documento.max' => 'La cédula es muy larga.',
            'telefono.max' => 'El WhatsApp es muy largo.',
            'direccion.max' => 'La dirección es muy larga (máximo 80 letras).',
        ]);
        $doc = preg_replace('/[^0-9A-Za-z-]/', '', (string) ($d['documento'] ?? ''));
        $tel = preg_replace('/\D/', '', (string) ($d['telefono'] ?? ''));

        // Si ya existe en esta tienda (misma cédula o mismo WhatsApp), se devuelve ese
        $existente = \App\Models\Cliente::with('persona')->whereHas('persona', function ($p) use ($doc, $tel) {
            $p->where(function ($w) use ($doc, $tel) {
                if ($doc !== '') $w->orWhere('numero_documento', $doc);
                if ($tel !== '') $w->orWhere('telefono', $tel);
            });
        })->when($doc === '' && $tel === '', fn ($q) => $q->whereRaw('1 = 0'))->first();

        if ($existente) {
            $c = $existente;
            $existia = true;
        } else {
            $c = DB::transaction(function () use ($d, $doc, $tel) {
                $persona = \App\Models\Persona::create([
                    'razon_social' => trim($d['nombre']),
                    'direccion' => trim((string) ($d['direccion'] ?? '')),
                    'telefono' => $tel ?: null,
                    'tipo_persona' => 'natural',
                    'documento_id' => \App\Models\Documento::orderByRaw("tipo_documento = 'Cédula de ciudadanía' desc")->value('id'),
                    'numero_documento' => $doc ?: ($tel ? 'TEL-' . $tel : 'C-' . now()->format('ymdHis')),
                ]);
                return $persona->cliente()->create(['persona_id' => $persona->id])->load('persona');
            });
            $existia = false;
        }

        return response()->json([
            'id' => $c->id,
            'nombre' => $c->persona->razon_social,
            'telefono' => $c->persona->telefono,
            'documento' => $c->persona->numero_documento,
            'existia' => $existia,
        ]);
    }

    // Tiquete para impresora térmica de 80 mm
    public function ticket(PosVenta $venta)
    {
        $this->autorizar($venta);
        return view('pos.ticket', [
            'venta' => $venta->load(['items', 'pagos.metodo', 'user:id,name']),
            'negocio' => Negocio::datos(),
        ]);
    }

    // Factura que ve el cliente desde el enlace de WhatsApp (enlace firmado, sin iniciar sesión)
    public function facturaPublica(Request $request, PosVenta $venta)
    {
        abort_unless($request->hasValidSignature(), 403, 'El enlace de la factura no es válido.');
        // La factura se muestra con los datos, moneda y zona horaria de la tienda que vendió
        \App\Support\TiendaActual::set($venta->tienda_id);
        $zona = \App\Support\Paises::datos(optional($venta->tienda)->pais)['zona'];
        date_default_timezone_set($zona);
        config(['app.timezone' => $zona]);
        return view('pos.factura', [
            'venta' => $venta->load(['items', 'pagos.metodo', 'user:id,name']),
            'negocio' => Negocio::datos(),
        ]);
    }

    public function ventas(Request $request)
    {
        $todas = Auth::user()->can('ver-cajas-todas');
        $ventas = PosVenta::with(['user:id,name', 'pagos.metodo'])
            ->when(!$todas, fn ($q) => $q->where('user_id', Auth::id()))
            ->when($request->get('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('numero', 'like', "%$t%")->orWhere('cliente_nombre', 'like', "%$t%")->orWhere('cliente_telefono', 'like', "%$t%")))
            ->latest()
            ->paginate(30)
            ->withQueryString();
        return view('pos.ventas', compact('ventas'));
    }

    // Anula la venta y devuelve las prendas al inventario
    public function anular(PosVenta $venta)
    {
        DB::transaction(function () use ($venta) {
            $venta = PosVenta::whereKey($venta->id)->lockForUpdate()->first();
            if ($venta->estado !== 'pagada') {
                return;
            }
            abort_if($venta->caja->estado !== 'abierta', 422, 'Solo se pueden anular ventas de una caja abierta.');
            abort_if(\App\Models\Devolucion::where('pos_venta_id', $venta->id)->exists(), 422, 'Esta venta tiene devoluciones; no se puede anular.');
            foreach ($venta->items as $item) {
                if ($item->variante && $item->variante->controla) {
                    $item->variante->moverStock((float) $item->cantidad, 'anulacion', $venta->numero);
                }
            }
            $venta->update(['estado' => 'anulada', 'anulada_en' => now(), 'anulada_por' => Auth::id()]);
        });
        return back()->with('success', 'Venta anulada y prendas devueltas al inventario.');
    }

    private function autorizar(PosVenta $venta): void
    {
        abort_unless((int) $venta->user_id === (int) Auth::id() || Auth::user()->can('ver-cajas-todas'), 403);
    }
}
