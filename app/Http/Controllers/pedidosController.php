<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PedidoEstacion;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Tienda;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pedidos de restaurante (también granjas, parques, cafeterías):
 * 1. El mesero toma el pedido con el nombre del cliente o la mesa.
 * 2. Caja lo cobra (antes de preparar o al final, según el negocio) desde el POS.
 * 3. Cada estación (Cocina, Cafetería, Entradas, Paseos…) ve en su pantalla solo lo suyo y lo marca listo.
 * 4. Al mesero le suena el aviso de "listo" y marca entregado.
 * 5. Cierre del día: ventas por mesero, por estación, lo más pedido y tiempos.
 */
class pedidosController extends Controller
{
    private function tienda(): Tienda
    {
        $t = TiendaActual::tienda();
        abort_unless($t && $t->mod_pedidos, 404, 'Los pedidos no están activos. Actívelos en Datos del negocio → Módulos.');
        return $t;
    }

    private function puede(string ...$permisos): bool
    {
        $u = Auth::user();
        if ($u->es_superadmin || $u->can('pedidos-administrar')) return true;
        foreach ($permisos as $p) if ($u->can($p)) return true;
        return false;
    }

    private function exigir(string ...$permisos): void
    {
        abort_unless($this->puede(...$permisos), 403, 'Su rol no tiene permiso para esta pantalla.');
    }

    // Estaciones que ve esta persona (su rol puede limitarlas)
    private function misEstaciones()
    {
        $q = PedidoEstacion::activas();
        $rol = Auth::user()->negocioRol;
        if ($rol && $rol->estacionIds() && !Auth::user()->can('pedidos-administrar')) $q->whereIn('id', $rol->estacionIds());
        return $q->get();
    }

    // ===== Entrada: cada rol va a su pantalla =====
    public function index()
    {
        $this->tienda();
        $u = Auth::user();
        if ($u->can('pedidos-administrar') || $u->es_superadmin) return redirect()->route('pedidos.caja');
        if ($u->can('pedidos-tomar')) return redirect()->route('pedidos.mesero');
        if ($u->can('pedidos-cobrar')) return redirect()->route('pedidos.caja');
        if ($u->can('pedidos-preparar')) return redirect()->route('pedidos.estacion');
        abort(403, 'Su rol no tiene permisos de pedidos.');
    }

    // ===== Mesero =====
    public function mesero()
    {
        $t = $this->tienda();
        $this->exigir('pedidos-tomar');
        return view('pedidos.mesero', ['tienda' => $t, 'todos' => $this->puede('pedidos-cobrar')]);
    }

    public function meseroDatos(Request $request)
    {
        $this->tienda();
        $this->exigir('pedidos-tomar');
        $todos = $request->boolean('todos') && $this->puede('pedidos-cobrar');
        $lista = Pedido::with('items:id,pedido_id,nombre,cantidad,estado,estacion_id,enviado_at', 'mesero:id,name')
            ->whereNotIn('estado', ['cerrado', 'anulado'])
            ->when(!$todos, fn ($q) => $q->where('user_id', Auth::id()))
            ->orderByRaw("estado = 'listo' desc")->orderBy('id')->limit(80)->get();
        return response()->json(['pedidos' => $lista->map(fn ($p) => $this->resumen($p))->values()]);
    }

    private function resumen(Pedido $p): array
    {
        $vis = $p->items->where('estado', '<>', 'anulado');
        return [
            'id' => $p->id, 'numero' => $p->numero, 'titulo' => $p->titulo, 'cliente' => $p->cliente_nombre, 'mesa' => $p->mesa,
            'estado' => $p->estado, 'estado_txt' => $p->estado_txt, 'pagado' => $p->pagado, 'modo' => $p->modo,
            'total' => $p->total, 'total_txt' => dinero($p->total), 'mesero' => $p->mesero?->name,
            'listos' => $vis->where('estado', 'listo')->count(), 'pendientes' => $vis->where('estado', 'pendiente')->whereNotNull('enviado_at')->count(),
            'items' => $vis->map(fn ($i) => ['n' => $i->nombre, 'c' => $i->cantidad, 'e' => $i->estado])->values(),
            'minutos' => $p->minutos, 'url' => route('pedidos.show', $p),
        ];
    }

    // Formulario para tomar el pedido (o agregar más a uno abierto)
    public function crear(Request $request, ?Pedido $pedido = null)
    {
        $t = $this->tienda();
        $this->exigir('pedidos-tomar');
        if ($pedido && $pedido->exists && ($pedido->pagado || !$pedido->abierto)) {
            return redirect()->route('pedidos.crear', ['cliente' => $pedido->cliente_nombre, 'mesa' => $pedido->mesa])
                ->with('success', 'Ese pedido ya se cobró: este es un pedido nuevo para el mismo cliente.');
        }
        return view('pedidos.crear', [
            'tienda' => $t, 'pedido' => $pedido?->exists ? $pedido : null,
            'catalogo' => $this->catalogo(),
            'cliente' => $request->query('cliente'), 'mesa' => $request->query('mesa'),
        ]);
    }

    // Productos para tomar pedidos, agrupados por categoría
    private function catalogo(): array
    {
        $vars = ProductoVariante::with(['producto:id,nombre,precio_venta,img_path,controla_inventario,estacion_id', 'producto.categorias.caracteristica:id,nombre'])
            ->where('estado', 1)->whereHas('producto', fn ($p) => $p->where('estado', 1))
            ->orderBy('producto_id')->limit(600)->get();
        $out = [];
        foreach ($vars->groupBy('producto_id') as $grupo) {
            $p = $grupo->first()->producto;
            $cat = optional(optional($p->categorias->first())->caracteristica)->nombre ?: 'Otros';
            foreach ($grupo as $v) {
                $out[] = [
                    'id' => $v->id, 'n' => $p->nombre, 'et' => $grupo->count() > 1 ? $v->etiqueta : '',
                    'p' => $v->precio_final, 'pt' => dinero($v->precio_final), 'cat' => $cat,
                    'ag' => $p->controla_inventario && (float) $v->stock <= 0,
                    'img' => $p->img_path ? asset('storage/productos/' . $p->img_path) : null,
                ];
            }
        }
        usort($out, fn ($a, $b) => [$a['cat'], $a['n']] <=> [$b['cat'], $b['n']]);
        return $out;
    }

    public function store(Request $request)
    {
        $t = $this->tienda();
        $this->exigir('pedidos-tomar');
        $d = $request->validate([
            'pedido_id' => 'nullable|integer',
            'cliente_nombre' => 'nullable|string|max:60',
            'mesa' => 'nullable|string|max:20',
            'nota' => 'nullable|string|max:255',
            'items' => 'required|array|min:1|max:80',
            'items.*.variante_id' => 'required|integer',
            'items.*.cantidad' => 'required|numeric|min:0.001|max:1000',
            'items.*.nota' => 'nullable|string|max:160',
        ], ['items.required' => 'Agregue al menos un producto.']);
        if (empty($d['pedido_id']) && trim((string) ($d['cliente_nombre'] ?? '')) === '' && trim((string) ($d['mesa'] ?? '')) === '') {
            throw ValidationException::withMessages(['cliente_nombre' => 'Escriba el nombre del cliente o el número de la mesa.']);
        }

        $pedido = DB::transaction(function () use ($d, $t) {
            $vars = ProductoVariante::with('producto:id,nombre,precio_venta,estacion_id')->whereIn('id', collect($d['items'])->pluck('variante_id'))->get()->keyBy('id');
            PedidoEstacion::asegurar();
            $estaciones = PedidoEstacion::activas()->pluck('id')->map(fn ($x) => (int) $x)->all();
            $porDefecto = $estaciones[0] ?? null;

            if (!empty($d['pedido_id'])) {
                $pedido = Pedido::whereKey($d['pedido_id'])->lockForUpdate()->firstOrFail();
                if ($pedido->pagado || !$pedido->abierto) throw ValidationException::withMessages(['items' => 'Ese pedido ya se cobró o se cerró. Haga un pedido nuevo.']);
                $ronda = (int) $pedido->items()->max('ronda') + 1;
                $pedido->cuenta_pedida = false;
            } else {
                $pedido = Pedido::create([
                    'numero' => Pedido::siguienteNumero($t),
                    'cliente_nombre' => trim((string) ($d['cliente_nombre'] ?? '')) ?: null,
                    'mesa' => trim((string) ($d['mesa'] ?? '')) ?: null,
                    'nota' => $d['nota'] ?? null, 'user_id' => Auth::id(),
                    'modo' => $t->pedidos_cobro === 'despues' ? 'despues' : 'antes', 'estado' => 'por_cobrar',
                ]);
                $ronda = 1;
            }
            // Modo "antes": llega a las estaciones cuando caja cobra. Modo "después": llega de una.
            $enviar = $pedido->modo === 'despues' ? now() : null;
            foreach ($d['items'] as $it) {
                $v = $vars[$it['variante_id']] ?? null;
                if (!$v) throw ValidationException::withMessages(['items' => 'Algún producto ya no existe. Recargue la página.']);
                $est = $v->producto->estacion_id && in_array((int) $v->producto->estacion_id, $estaciones, true) ? (int) $v->producto->estacion_id : $porDefecto;
                $cant = round((float) $it['cantidad'], 3);
                $pedido->items()->create([
                    'producto_id' => $v->producto_id, 'producto_variante_id' => $v->id, 'estacion_id' => $est,
                    'nombre' => $v->producto->nombre, 'etiqueta' => ($v->talla || $v->color) ? ($v->etiqueta ?: null) : null,
                    'cantidad' => $cant, 'precio' => $v->precio_final, 'subtotal' => round($cant * $v->precio_final, 2),
                    'nota' => $it['nota'] ?? null, 'ronda' => $ronda, 'enviado_at' => $enviar,
                ]);
            }
            $pedido->recalcular();
            return $pedido;
        });

        $msg = $pedido->modo === 'antes' && !$pedido->pagado
            ? "Pedido {$pedido->titulo} enviado a caja para cobrar."
            : "Pedido {$pedido->titulo} enviado a preparar.";
        return response()->json(['ok' => true, 'mensaje' => $msg, 'url' => route('pedidos.mesero')]);
    }

    public function show(Pedido $pedido)
    {
        $this->tienda();
        $this->exigir('pedidos-tomar', 'pedidos-cobrar', 'pedidos-preparar');
        $pedido->load('items.estacion', 'mesero', 'venta');
        return view('pedidos.show', ['pedido' => $pedido]);
    }

    // El mesero entregó lo que estaba listo
    public function entregar(Pedido $pedido)
    {
        $this->tienda();
        $this->exigir('pedidos-tomar');
        DB::transaction(function () use ($pedido) {
            $pedido->items()->where('estado', 'listo')->update(['estado' => 'entregado', 'entregado_at' => now()]);
            $pedido->entregado_at = now();
            $pedido->recalcular();
        });
        return back()->with('success', 'Pedido ' . $pedido->titulo . ' entregado.');
    }

    // Modo "después": el cliente pide la cuenta y el pedido pasa a caja
    public function cuenta(Pedido $pedido)
    {
        $this->tienda();
        $this->exigir('pedidos-tomar', 'pedidos-cobrar');
        abort_if($pedido->pagado || !$pedido->abierto, 422, 'Este pedido ya se cobró.');
        $pedido->cuenta_pedida = true;
        $pedido->recalcular();
        return back()->with('success', 'La cuenta de ' . $pedido->titulo . ' pasó a caja: ' . dinero($pedido->total) . '.');
    }

    public function anular(Pedido $pedido)
    {
        $this->tienda();
        $this->exigir('pedidos-tomar', 'pedidos-cobrar');
        if ($pedido->pagado) return back()->withErrors('Este pedido ya se cobró: anule la venta en Ventas para devolver el dinero.');
        DB::transaction(function () use ($pedido) {
            $pedido->items()->update(['estado' => 'anulado']);
            $pedido->forceFill(['estado' => 'anulado', 'cerrado_at' => now()])->save();
        });
        return redirect()->route('pedidos.index')->with('success', 'Pedido ' . $pedido->titulo . ' anulado.');
    }

    public function anularItem(PedidoItem $item)
    {
        $this->tienda();
        $this->exigir('pedidos-tomar', 'pedidos-cobrar');
        $pedido = Pedido::findOrFail($item->pedido_id);
        if ($pedido->pagado) return back()->withErrors('El pedido ya se cobró: no se puede quitar el producto.');
        $item->update(['estado' => 'anulado']);
        $pedido->recalcular();
        return back()->with('success', 'Se quitó ' . $item->nombre . ' del pedido.');
    }

    // ===== Caja: pedidos por cobrar =====
    public function caja()
    {
        $t = $this->tienda();
        $this->exigir('pedidos-cobrar');
        return view('pedidos.caja', [
            'tienda' => $t,
            'porCobrar' => Pedido::with('items', 'mesero:id,name')->where('estado', 'por_cobrar')->where('pagado', false)->orderBy('id')->get(),
            'abiertos' => Pedido::with('items', 'mesero:id,name')->whereIn('estado', ['en_preparacion', 'listo', 'entregado'])->orderBy('id')->get(),
            'hoy' => Pedido::whereDate('created_at', today())->where('pagado', true)->selectRaw('COUNT(*) n, COALESCE(SUM(total),0) t')->first(),
            'caja' => \App\Models\Caja::abiertaDe(Auth::id()),
        ]);
    }

    public function cajaDatos()
    {
        $this->tienda();
        $this->exigir('pedidos-cobrar');
        return response()->json(['por_cobrar' => Pedido::where('estado', 'por_cobrar')->where('pagado', false)->count(), 'ultimo' => (int) Pedido::max('id')]);
    }

    // ===== Estaciones (cocina, cafetería, entradas…) =====
    public function estacion(Request $request)
    {
        $t = $this->tienda();
        $this->exigir('pedidos-preparar');
        PedidoEstacion::asegurar();
        $mias = $this->misEstaciones();
        return view('pedidos.estacion', ['tienda' => $t, 'estaciones' => $mias, 'sel' => $mias->firstWhere('id', (int) $request->query('e'))]);
    }

    public function estacionDatos(Request $request)
    {
        $this->tienda();
        $this->exigir('pedidos-preparar');
        $ids = $this->misEstaciones()->pluck('id')->all();
        if ($e = (int) $request->query('e')) $ids = array_values(array_intersect($ids, [$e]));
        $pedidos = Pedido::where('estado', '<>', 'anulado')->where('created_at', '>=', now()->subDay())->pluck('id');
        $items = PedidoItem::with('estacion:id,nombre')
            ->whereIn('pedido_id', $pedidos)->whereIn('estacion_id', $ids)->whereNotNull('enviado_at')
            ->where(fn ($q) => $q->where('estado', 'pendiente')->orWhere(fn ($w) => $w->where('estado', 'listo')->where('listo_at', '>=', now()->subMinutes(20))))
            ->orderBy('enviado_at')->orderBy('id')->limit(400)->get();
        $peds = Pedido::with('mesero:id,name')->whereIn('id', $items->pluck('pedido_id')->unique())->get()->keyBy('id');
        $grupos = $items->groupBy('pedido_id')->map(function ($its, $pid) use ($peds) {
            $p = $peds[$pid];
            return [
                'id' => $p->id, 'numero' => $p->numero, 'cliente' => $p->cliente_nombre, 'mesa' => $p->mesa, 'nota' => $p->nota,
                'mesero' => $p->mesero?->name, 'pagado' => $p->pagado, 'desde' => $its->min('enviado_at')?->toIso8601String(),
                'listo' => $its->every(fn ($i) => $i->estado === 'listo'), 'comanda' => route('pedidos.comanda', $p),
                'items' => $its->map(fn ($i) => ['id' => $i->id, 'n' => $i->nombre, 'et' => $i->etiqueta, 'c' => $i->cantidad, 'nota' => $i->nota, 'e' => $i->estado, 'est' => $i->estacion?->nombre, 'ronda' => $i->ronda])->values(),
            ];
        })->sortBy(fn ($g) => ($g['listo'] ? '1' : '0') . $g['desde'])->values();
        return response()->json(['pedidos' => $grupos, 'ultimo' => (int) $items->max('id'), 'ahora' => now()->toIso8601String()]);
    }

    // Marcar listo (o deshacer) productos de la estación
    public function marcar(Request $request)
    {
        $this->tienda();
        $this->exigir('pedidos-preparar');
        $d = $request->validate(['items' => 'required|array|max:100', 'items.*' => 'integer', 'deshacer' => 'nullable|boolean']);
        $ids = $this->misEstaciones()->pluck('id')->all();
        $pedidosTienda = Pedido::where('created_at', '>=', now()->subDays(2))->pluck('id');
        DB::transaction(function () use ($d, $ids, $pedidosTienda) {
            $q = PedidoItem::whereIn('id', $d['items'])->whereIn('pedido_id', $pedidosTienda)->whereIn('estacion_id', $ids);
            $afectados = (clone $q)->pluck('pedido_id')->unique();
            if (!empty($d['deshacer'])) $q->where('estado', 'listo')->update(['estado' => 'pendiente', 'listo_at' => null]);
            else $q->where('estado', 'pendiente')->update(['estado' => 'listo', 'listo_at' => now()]);
            foreach (Pedido::whereIn('id', $afectados)->get() as $p) $p->recalcular();
        });
        return response()->json(['ok' => true]);
    }

    // Comanda para imprimir (toda o solo de una estación)
    public function comanda(Request $request, Pedido $pedido)
    {
        $this->tienda();
        $this->exigir('pedidos-preparar', 'pedidos-tomar', 'pedidos-cobrar');
        $e = (int) $request->query('e');
        $items = $pedido->items()->with('estacion:id,nombre')->where('estado', '<>', 'anulado')->when($e, fn ($q) => $q->where('estacion_id', $e))->get();
        return view('pedidos.comanda', ['pedido' => $pedido->load('mesero'), 'items' => $items, 'estacion' => $e ? PedidoEstacion::find($e) : null, 'tienda' => TiendaActual::tienda()]);
    }

    // ===== Configuración (dueño / administrador) =====
    public function configurar()
    {
        $t = $this->tienda();
        $this->exigir('pedidos-administrar');
        PedidoEstacion::asegurar();
        if (!$t->pedidos_token) {
            DB::table('tiendas')->where('id', $t->id)->update(['pedidos_token' => Str::lower(Str::random(32))]);
            $t->refresh();
        }
        $productos = Producto::with('categorias.caracteristica:id,nombre')->where('estado', 1)->orderBy('nombre')->get(['id', 'nombre', 'estacion_id']);
        $catDe = fn ($p) => optional(optional($p->categorias->first())->caracteristica)->nombre ?: 'Otros';
        return view('pedidos.configurar', [
            'tienda' => $t, 'estaciones' => PedidoEstacion::orderBy('orden')->orderBy('id')->get(), 'productos' => $productos,
            'catDe' => $catDe, 'categorias' => $productos->map($catDe)->unique()->sort()->values(),
        ]);
    }

    public function guardarConfig(Request $request)
    {
        $t = $this->tienda();
        $this->exigir('pedidos-administrar');
        $d = $request->validate([
            'pedidos_cobro' => 'required|in:antes,despues',
            'est' => 'nullable|array',
            'est.*.nombre' => 'required|string|max:40',
            'est.*.icono' => 'nullable|string|max:40|regex:/^fa-[a-z0-9-]+$/',
            'nueva' => 'nullable|string|max:40',
            'nueva_icono' => 'nullable|string|max:40|regex:/^fa-[a-z0-9-]+$/',
        ], ['est.*.nombre.required' => 'Cada estación necesita un nombre.']);
        DB::transaction(function () use ($request, $d, $t) {
            DB::table('tiendas')->where('id', $t->id)->update(['pedidos_cobro' => $d['pedidos_cobro']]);
            foreach ((array) ($d['est'] ?? []) as $id => $e) {
                PedidoEstacion::whereKey($id)->update([
                    'nombre' => trim($e['nombre']), 'icono' => $e['icono'] ?? 'fa-kitchen-set',
                    'imprimir' => $request->boolean("est.$id.imprimir"), 'activo' => $request->boolean("est.$id.activo"),
                ]);
            }
            if (trim((string) ($d['nueva'] ?? '')) !== '') {
                PedidoEstacion::create(['nombre' => trim($d['nueva']), 'icono' => $d['nueva_icono'] ?? 'fa-kitchen-set', 'orden' => (int) PedidoEstacion::max('orden') + 1]);
            }
            if (!PedidoEstacion::where('activo', true)->exists()) PedidoEstacion::orderBy('orden')->first()?->update(['activo' => true]);
        });
        return back()->with('success', 'Configuración de pedidos guardada.');
    }

    public function borrarEstacion(PedidoEstacion $estacion)
    {
        $this->tienda();
        $this->exigir('pedidos-administrar');
        if (PedidoItem::where('estacion_id', $estacion->id)->where('estado', 'pendiente')->exists()) {
            return back()->withErrors('La estación ' . $estacion->nombre . ' tiene pedidos pendientes. Apáguela en vez de borrarla.');
        }
        if (PedidoEstacion::count() <= 1) return back()->withErrors('Debe quedar al menos una estación.');
        Producto::where('estacion_id', $estacion->id)->update(['estacion_id' => null]);
        $estacion->delete();
        return back()->with('success', 'Estación eliminada. Sus productos pasan a la primera estación.');
    }

    // Qué estación prepara cada producto
    public function asignarProductos(Request $request)
    {
        $this->tienda();
        $this->exigir('pedidos-administrar');
        $d = $request->validate(['prod' => 'nullable|array', 'prod.*' => 'nullable|integer', 'categoria' => 'nullable|string|max:80', 'categoria_estacion' => 'nullable|integer']);
        $validas = PedidoEstacion::pluck('id')->map(fn ($x) => (int) $x)->all();
        $n = 0;
        DB::transaction(function () use ($d, $validas, &$n) {
            if (!empty($d['categoria']) && in_array((int) ($d['categoria_estacion'] ?? 0), $validas, true)) {
                $ids = Producto::with('categorias.caracteristica:id,nombre')->where('estado', 1)->get()
                    ->filter(fn ($p) => (optional(optional($p->categorias->first())->caracteristica)->nombre ?: 'Otros') === $d['categoria'])->pluck('id');
                $n += Producto::whereIn('id', $ids)->update(['estacion_id' => (int) $d['categoria_estacion']]);
                return;
            }
            foreach ((array) ($d['prod'] ?? []) as $pid => $eid) {
                $eid = in_array((int) $eid, $validas, true) ? (int) $eid : null;
                $n += Producto::whereKey((int) $pid)->where(fn ($q) => $q->whereNull('estacion_id')->orWhere('estacion_id', '<>', $eid ?? 0))->update(['estacion_id' => $eid]);
            }
        });
        return back()->with('success', $n ? "Listo: {$n} producto(s) con su estación." : 'No hubo cambios.');
    }

    // ===== Cierre del día =====
    public function cierre(Request $request)
    {
        $t = $this->tienda();
        $this->exigir('pedidos-administrar');
        try {
            $fecha = $request->filled('fecha') ? \Carbon\Carbon::parse((string) $request->query('fecha'))->startOfDay() : today();
        } catch (\Throwable $e) {
            $fecha = today();
        }
        $peds = Pedido::with('items.estacion:id,nombre', 'mesero:id,name')->whereDate('created_at', $fecha)->get();
        $validos = $peds->where('estado', '<>', 'anulado');
        $items = $validos->flatMap(fn ($p) => $p->items)->where('estado', '<>', 'anulado');
        $min = fn ($a, $b) => $a && $b ? max(0, $a->diffInSeconds($b) / 60) : null;
        return view('pedidos.cierre', [
            'tienda' => $t, 'fecha' => $fecha, 'pedidos' => $peds,
            'cobrado' => (float) $validos->where('pagado', true)->sum('total'),
            'sinCobrar' => $validos->where('pagado', false),
            'anulados' => $peds->where('estado', 'anulado')->count(),
            'porMesero' => $validos->groupBy(fn ($p) => $p->mesero?->name ?: 'Sin mesero')->map(fn ($g) => ['n' => $g->count(), 't' => (float) $g->sum('total')])->sortByDesc('t'),
            'porEstacion' => $items->groupBy(fn ($i) => $i->estacion?->nombre ?: 'Sin estación')->map(function ($g) use ($min) {
                $ts = $g->map(fn ($i) => $min($i->enviado_at, $i->listo_at))->filter(fn ($x) => $x !== null);
                return ['n' => (float) $g->sum('cantidad'), 'prom' => $ts->count() ? round($ts->avg(), 1) : null, 'max' => $ts->count() ? round($ts->max(), 1) : null];
            }),
            'top' => $items->groupBy(fn ($i) => $i->nombre . ($i->etiqueta ? ' (' . $i->etiqueta . ')' : ''))
                ->map(fn ($g) => ['c' => (float) $g->sum('cantidad'), 't' => (float) $g->sum('subtotal')])->sortByDesc('c')->take(15),
        ]);
    }

    // ===== Manual de uso para el negocio =====
    public function guia()
    {
        return view('pedidos.guia', ['tienda' => $this->tienda()]);
    }

    // ===== Pantalla para los clientes (sin iniciar sesión): "Su pedido está listo" =====
    public function pantallaClientes(string $token)
    {
        $t = Tienda::where('pedidos_token', $token)->where('mod_pedidos', true)->firstOrFail();
        return view('pedidos.pantalla-clientes', ['tienda' => $t, 'token' => $token]);
    }

    public function pantallaClientesDatos(string $token)
    {
        $t = Tienda::where('pedidos_token', $token)->where('mod_pedidos', true)->firstOrFail();
        $base = Pedido::withoutGlobalScope('tienda')->where('tienda_id', $t->id)->where('created_at', '>=', now()->subHours(12));
        $nom = fn ($p) => ['n' => $p->numero, 't' => $p->cliente_nombre ? Str::limit(Str::title(Str::lower($p->cliente_nombre)), 22, '') : ($p->mesa ? 'Mesa ' . $p->mesa : '#' . $p->numero)];
        return response()->json([
            'listos' => (clone $base)->where('estado', 'listo')->orderByDesc('listo_at')->limit(12)->get()->map($nom)->values(),
            'preparando' => (clone $base)->where('estado', 'en_preparacion')->orderBy('id')->limit(20)->get()->map($nom)->values(),
        ]);
    }
}
