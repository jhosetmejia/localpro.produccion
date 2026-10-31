<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Tienda;
use App\Support\Disponibilidad;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

// Cotizaciones: se arman con los productos creados (precio del producto o cambiado a mano) o con líneas libres,
// salen en PDF con el logo del negocio y se mandan por WhatsApp con un enlace. Con la fecha del alquiler
// muestran la disponibilidad: lo que está en cotizaciones aceptadas para esas fechas queda ocupado.
class cotizacionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            abort_unless($u && ($u->es_superadmin || $u->can('usar-pos') || $u->can('usar-alquileres')), 403);
            return $next($request);
        })->except('publica');
    }

    public function index(Request $request)
    {
        $ver = $request->get('ver', 'todas');
        $q = trim((string) $request->get('q'));
        $lista = Cotizacion::query()
            ->when($ver === 'pendientes', fn ($w) => $w->where('estado', 'pendiente')->where(fn ($x) => $x->whereNull('vence')->orWhereDate('vence', '>=', today())))
            ->when($ver === 'aceptadas', fn ($w) => $w->where('estado', 'aceptada'))
            ->when($ver === 'vencidas', fn ($w) => $w->where('estado', 'pendiente')->whereDate('vence', '<', today()))
            ->when($ver === 'rechazadas', fn ($w) => $w->where('estado', 'rechazada'))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('numero', 'like', "%$q%")->orWhere('cliente_nombre', 'like', "%$q%")
                ->orWhere('cliente_telefono', 'like', "%$q%")->orWhere('asunto', 'like', "%$q%")))
            ->orderByDesc('id')
            ->paginate(30)->withQueryString();

        $pend = Cotizacion::where('estado', 'pendiente')->where(fn ($x) => $x->whereNull('vence')->orWhereDate('vence', '>=', today()));
        return view('cotizacion.index', [
            'lista' => $lista, 'ver' => $ver, 'q' => $q,
            'kpi' => [
                'pendientes' => (clone $pend)->count(),
                'valorPendientes' => (float) (clone $pend)->sum('total'),
                'aceptadasMes' => Cotizacion::where('estado', 'aceptada')->whereYear('updated_at', now()->year)->whereMonth('updated_at', now()->month)->count(),
                'valorAceptadasMes' => (float) Cotizacion::where('estado', 'aceptada')->whereYear('updated_at', now()->year)->whereMonth('updated_at', now()->month)->sum('total'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $c = new Cotizacion([
            'fecha' => today(), 'vence' => today()->addDays(15), 'estado' => 'pendiente',
            'condiciones' => Cotizacion::whereNotNull('condiciones')->latest('id')->value('condiciones') ?? Cotizacion::CONDICIONES,
        ]);
        // Duplicar: arranca con los datos y productos de otra cotización
        $items = [];
        $copiaLogo = null;
        if ($request->filled('copiar') && ($o = Cotizacion::with('items')->find($request->integer('copiar')))) {
            $c->fill($o->only(['cliente_id', 'cliente_nombre', 'cliente_documento', 'cliente_telefono', 'cliente_email', 'cliente_direccion', 'asunto', 'evento_lugar', 'evento_hora', 'evento_hora_fin', 'descuento', 'condiciones']));
            $copiaLogo = $o->logo_path ? $o : null;
            $items = $o->items->map->only(['producto_id', 'descripcion', 'cantidad', 'precio', 'precio_lista'])->all();
        }
        return view('cotizacion.form', ['c' => $c, 'items' => $items, 'copiaLogo' => $copiaLogo]);
    }

    public function store(Request $request)
    {
        [$d, $items] = $this->validar($request);
        $c = DB::transaction(function () use ($d, $items) {
            Tienda::whereKey(TiendaActual::id())->lockForUpdate()->first();
            $c = Cotizacion::create($d + ['numero' => Cotizacion::siguienteNumero(), 'user_id' => Auth::id(), 'estado' => 'pendiente']);
            $this->guardarItems($c, $items);
            return $c;
        });
        $this->guardarLogo($request, $c);
        return redirect()->route('cotizaciones.show', $c)->with('success', 'Cotización ' . $c->numero . ' creada. Descárguela en PDF o envíela por WhatsApp.');
    }

    public function show(Cotizacion $cotizacion)
    {
        return view('cotizacion.ver', $this->datosVer($cotizacion, false));
    }

    // Lo que ve el cliente desde el enlace de WhatsApp (sin entrar al sistema)
    public function publica(Cotizacion $cotizacion)
    {
        TiendaActual::set($cotizacion->tienda_id); // valores en la moneda de esa tienda
        return view('cotizacion.publica', $this->datosVer($cotizacion, true));
    }

    public function edit(Cotizacion $cotizacion)
    {
        $items = $cotizacion->items->map->only(['producto_id', 'descripcion', 'cantidad', 'precio', 'precio_lista'])->all();
        return view('cotizacion.form', ['c' => $cotizacion, 'items' => $items]);
    }

    public function update(Request $request, Cotizacion $cotizacion)
    {
        [$d, $items] = $this->validar($request, $cotizacion);
        DB::transaction(function () use ($cotizacion, $d, $items) {
            $cotizacion->update($d);
            $cotizacion->items()->delete();
            $this->guardarItems($cotizacion, $items);
        });
        $this->guardarLogo($request, $cotizacion);
        return redirect()->route('cotizaciones.show', $cotizacion)->with('success', 'Cotización ' . $cotizacion->numero . ' guardada.');
    }

    // Aceptada (ocupa la disponibilidad de esas fechas), no aceptada o de nuevo pendiente
    public function estado(Request $request, Cotizacion $cotizacion)
    {
        $d = $request->validate(['estado' => 'required|in:' . implode(',', array_keys(Cotizacion::ESTADOS))]);
        if ($d['estado'] === 'aceptada' && $cotizacion->estado !== 'aceptada' && $cotizacion->evento_fecha) {
            // Aviso si al aceptarla se pasa de lo que tiene
            $cotizacion->load('items');
            $disp = Disponibilidad::productos($cotizacion->items->pluck('producto_id')->all(), $cotizacion->evento_fecha, $cotizacion->evento_hasta, $cotizacion->id);
            $faltan = $cotizacion->items->filter(fn ($i) => $i->producto_id && isset($disp[$i->producto_id]) && $disp[$i->producto_id]['libre'] !== null && $i->cantidad > $disp[$i->producto_id]['libre'])
                ->map(fn ($i) => $i->descripcion . ' (pide ' . $i->cantidad_texto . ', quedan ' . rtrim(rtrim(number_format($disp[$i->producto_id]['libre'], 3, ',', '.'), '0'), ',') . ')');
            if ($faltan->isNotEmpty() && !$request->boolean('confirmado')) {
                return back()->with('aceptarIgual', $faltan->implode(' · '));
            }
        }
        $cotizacion->update(['estado' => $d['estado']]);
        $txt = ['aceptada' => 'quedó aceptada' . ($cotizacion->evento_fecha ? ': lo cotizado queda ocupado del ' . $cotizacion->evento_texto : ''), 'rechazada' => 'quedó como no aceptada', 'pendiente' => 'volvió a pendiente'][$d['estado']];
        return back()->with('success', 'La cotización ' . $cotizacion->numero . ' ' . $txt . '.');
    }

    public function destroy(Cotizacion $cotizacion)
    {
        $n = $cotizacion->numero;
        $logo = $cotizacion->logo_path;
        $cotizacion->delete();
        if ($logo) Storage::disk('public')->delete($logo);
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización ' . $n . ' eliminada.');
    }

    // Buscador de productos para armar la cotización (incluye los agotados: se cotiza aunque hoy no haya)
    public function productos(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $lista = Producto::query()
            ->where('estado', 1)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nombre', 'like', "%$q%")->orWhere('codigo', 'like', "%$q%")
                ->orWhereHas('variantes', fn ($v) => $v->where('codigo_barras', $q))))
            ->withSum(['variantes as unidades' => fn ($v) => $v->where('estado', 1)], 'stock')
            ->orderBy('nombre')->limit(40)
            ->get(['id', 'nombre', 'codigo', 'precio_venta', 'img_path', 'controla_inventario']);
        return response()->json($lista->map(fn ($p) => [
            'id' => $p->id, 'nombre' => $p->nombre, 'codigo' => $p->codigo, 'precio' => (float) $p->precio_venta,
            'img' => $p->img_path ? asset('storage/productos/' . $p->img_path) : null,
            'unidades' => $p->controla_inventario ? round((float) $p->unidades, 3) : null,
        ]));
    }

    // Clientes ya creados, para no escribir sus datos otra vez
    public function clientes(Request $request)
    {
        $q = trim((string) $request->get('q'));
        if (mb_strlen($q) < 2) return response()->json([]);
        $lista = Cliente::with('persona')
            ->whereHas('persona', fn ($p) => $p->where('razon_social', 'like', "%$q%")->orWhere('numero_documento', 'like', "%$q%")->orWhere('telefono', 'like', "%$q%"))
            ->limit(10)->get();
        return response()->json($lista->map(fn ($c) => [
            'id' => $c->id, 'nombre' => $c->persona?->razon_social, 'documento' => $c->persona?->numero_documento,
            'telefono' => $c->persona?->telefono, 'email' => $c->persona?->email, 'direccion' => $c->persona?->direccion,
        ])->filter(fn ($c) => $c['nombre'])->values());
    }

    // Disponibilidad de los productos de la cotización para las fechas del alquiler (la usa el formulario)
    public function disponibilidad(Request $request)
    {
        $d = $request->validate(['desde' => 'required|date', 'hasta' => 'nullable|date', 'productos' => 'array', 'productos.*' => 'integer', 'excluir' => 'nullable|integer']);
        return response()->json([
            'productos' => Disponibilidad::productos($d['productos'] ?? [], $d['desde'], $d['hasta'] ?? null, $d['excluir'] ?? null),
            'alquileres' => Disponibilidad::alquileres($d['desde'], $d['hasta'] ?? null)->map(fn ($a) => [
                'numero' => $a->numero, 'que' => $a->que_alquila, 'fecha' => $a->fecha->format('d/m'), 'lugar' => $a->lugar,
                'alquilado' => $a->entrega === 'alquilado',
            ]),
        ]);
    }

    // Página: qué tiene libre y ocupado en una fecha (todos los productos que llevan cantidad)
    public function calendario(Request $request)
    {
        $desde = $request->date('desde') ?? today();
        $hasta = $request->date('hasta') ?? $desde;
        if ($hasta->lt($desde)) $hasta = $desde->copy();
        $q = trim((string) $request->get('q'));
        // Solo lo que se alquila (lo que solo se vende no sale aquí)
        // Con Alquiler: todo lo marcado "para alquilar", lleve o no cantidad (una plataforma 360 creada como servicio también sale)
        $conAlquiler = (bool) TiendaActual::tienda()?->mod_alquiler;
        $ids = Producto::where('estado', 1)
            ->when($conAlquiler, fn ($w) => $w->where('se_alquila', true), fn ($w) => $w->where('controla_inventario', 1))
            ->when($q !== '', fn ($w) => $w->where('nombre', 'like', "%$q%"))
            ->orderBy('nombre')->limit(300)->pluck('id')->all();
        $disp = collect(Disponibilidad::productos($ids, $desde, $hasta));
        return view('cotizacion.disponibilidad', [
            'desde' => $desde, 'hasta' => $hasta, 'q' => $q,
            'disp' => $disp->sortBy(fn ($x) => [$x['ocupado'] > 0 ? 0 : 1, $x['nombre']]),
            'alquileres' => Disponibilidad::alquileres($desde, $hasta),
        ]);
    }

    // Pasar la cotización a un alquiler (abre Nuevo alquiler con los datos ya puestos)
    public function alquiler(Cotizacion $cotizacion)
    {
        abort_unless(TiendaActual::tienda()?->mod_alquiler, 404);
        if ($cotizacion->alquiler_id && $cotizacion->alquiler) {
            return redirect()->route('alquileres.edit', $cotizacion->alquiler_id)->with('info', 'Esta cotización ya pasó al alquiler ' . $cotizacion->alquiler->numero . '.');
        }
        return redirect()->route('alquileres.create', ['cotizacion' => $cotizacion->id]);
    }

    private function datosVer(Cotizacion $c, bool $publica): array
    {
        $c->load('items', 'user');
        $tienda = Tienda::find($c->tienda_id);
        return ['c' => $c, 'tienda' => $tienda, 'publica' => $publica,
            'disp' => !$publica && $c->evento_fecha ? Disponibilidad::productos($c->items->pluck('producto_id')->all(), $c->evento_fecha, $c->evento_hasta, $c->id) : [],
            'whatsapp' => $publica ? null : $c->mensajeWhatsapp($tienda->nombre)];
    }

    private function validar(Request $request, ?Cotizacion $c = null): array
    {
        $d = $request->validate([
            'cliente_id' => 'nullable|integer',
            'cliente_nombre' => 'required|string|max:120',
            'cliente_documento' => 'nullable|string|max:30',
            'cliente_telefono' => 'nullable|string|max:30',
            'cliente_email' => 'nullable|email|max:120',
            'cliente_direccion' => 'nullable|string|max:150',
            'asunto' => 'nullable|string|max:150',
            'evento_fecha' => 'nullable|date',
            'evento_hasta' => 'nullable|date|after_or_equal:evento_fecha',
            'evento_hora' => 'nullable|date_format:H:i',
            'evento_hora_fin' => 'nullable|date_format:H:i',
            'evento_lugar' => 'nullable|string|max:150',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'logo_modo' => 'nullable|in:negocio,otro',
            'logo_copiar' => 'nullable|integer',
            'fecha' => 'required|date',
            'vence' => 'nullable|date|after_or_equal:fecha',
            'descuento' => 'nullable|numeric|min:0',
            'condiciones' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'nullable|integer',
            'items.*.descripcion' => 'required|string|max:200',
            'items.*.cantidad' => 'required|numeric|min:0.001|max:999999',
            'items.*.precio' => 'required|numeric|min:0|max:9999999999',
            'items.*.precio_lista' => 'nullable|numeric|min:0',
        ], [
            'cliente_nombre.required' => 'Escriba para quién es la cotización.',
            'items.required' => 'Agregue al menos un producto o una línea.',
            'items.min' => 'Agregue al menos un producto o una línea.',
            'items.*.descripcion.required' => 'Cada línea necesita una descripción.',
            'items.*.cantidad.min' => 'La cantidad debe ser mayor que cero.',
            'evento_hasta.after_or_equal' => 'La fecha final del alquiler no puede ser antes de la inicial.',
            'vence.after_or_equal' => 'La validez no puede ser antes de la fecha de la cotización.',
            'evento_hora.date_format' => 'Escriba la hora completa (hora y minutos).',
            'evento_hora_fin.date_format' => 'Escriba la hora final completa (hora y minutos).',
            'logo.image' => 'El logo debe ser una imagen (PNG, JPG o WEBP).',
            'logo.mimes' => 'El logo debe ser PNG, JPG o WEBP.',
            'logo.max' => 'El logo no puede pesar más de 2 MB.',
        ]);
        unset($d['logo'], $d['logo_modo'], $d['logo_copiar']);
        $items = array_values($d['items']);
        unset($d['items']);
        if (!empty($d['cliente_id']) && !Cliente::whereKey($d['cliente_id'])->exists()) $d['cliente_id'] = null;
        $validos = Producto::whereIn('id', array_filter(array_column($items, 'producto_id')))->pluck('id')->all();
        $subtotal = 0;
        foreach ($items as $i => $it) {
            $items[$i]['producto_id'] = in_array((int) ($it['producto_id'] ?? 0), $validos, false) ? (int) $it['producto_id'] : null;
            $items[$i]['subtotal'] = round((float) $it['cantidad'] * (float) $it['precio'], 2);
            $subtotal += $items[$i]['subtotal'];
        }
        $d['descuento'] = round((float) ($d['descuento'] ?? 0), 2);
        if ($d['descuento'] > $subtotal) throw ValidationException::withMessages(['descuento' => 'El descuento no puede ser mayor que el subtotal.']);
        $d['subtotal'] = round($subtotal, 2);
        $d['total'] = round($subtotal - $d['descuento'], 2);
        if (empty($d['evento_fecha'])) $d['evento_hasta'] = null;
        foreach (['evento_hora', 'evento_hora_fin'] as $h) $d[$h] = $d[$h] ?? null;
        return [$d, $items];
    }

    // Logo propio de la cotización: subir uno, quitarlo (vuelve el del negocio) o, al duplicar, copiar el de la original
    private function guardarLogo(Request $request, Cotizacion $c): void
    {
        $disco = Storage::disk('public');
        $carpeta = 'cotizaciones/' . $c->tienda_id;
        if ($request->input('logo_modo') === 'negocio') {
            if ($c->logo_path) { $disco->delete($c->logo_path); $c->forceFill(['logo_path' => null])->save(); }
            return;
        }
        if ($request->hasFile('logo')) {
            $ruta = $request->file('logo')->store($carpeta, 'public');
            if ($c->logo_path) $disco->delete($c->logo_path);
            $c->forceFill(['logo_path' => $ruta])->save();
            return;
        }
        // Duplicada: copia del archivo para que borrar una no le quite el logo a la otra
        if (!$c->logo_path && $request->filled('logo_copiar') && ($o = Cotizacion::find($request->integer('logo_copiar'))) && $o->logo_path && $disco->exists($o->logo_path)) {
            $nueva = $carpeta . '/' . \Illuminate\Support\Str::random(40) . '.' . pathinfo($o->logo_path, PATHINFO_EXTENSION);
            if ($disco->copy($o->logo_path, $nueva)) $c->forceFill(['logo_path' => $nueva])->save();
        }
    }

    private function guardarItems(Cotizacion $c, array $items): void
    {
        foreach ($items as $n => $it) {
            $c->items()->create([
                'producto_id' => $it['producto_id'], 'descripcion' => $it['descripcion'], 'cantidad' => $it['cantidad'], 'precio' => $it['precio'],
                'precio_lista' => $it['producto_id'] ? ($it['precio_lista'] ?? null) : null, 'subtotal' => $it['subtotal'], 'orden' => $n,
            ]);
        }
    }
}
