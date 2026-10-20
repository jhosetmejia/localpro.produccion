<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\MetodoPago;
use App\Models\PosVenta;
use App\Models\Tienda;
use App\Models\Tiquetera;
use App\Models\TiqueteraUso;
use App\Support\Dian\Facturador;
use App\Support\Dian\Impuestos;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tiquetera (restaurantes y cafeterías): el cliente paga por adelantado N almuerzos o servicios.
 * - La venta de la tiquetera entra a la caja abierta como una venta normal (cuadra la caja, los reportes y la DIAN).
 * - Cada vez que el cliente consume se marca un uso: no se cobra de nuevo.
 */
class tiqueteraController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:usar-pos');
    }

    private function esAdmin(): bool
    {
        $u = Auth::user();
        return (bool) ($u?->es_superadmin || $u?->hasAnyRole(['dueno', 'administrador']));
    }

    public function index(Request $request)
    {
        $ver = $request->query('ver', 'activas');
        $q = trim((string) $request->query('q'));
        $lista = Tiquetera::query()
            ->when($ver === 'activas', fn ($x) => $x->where('estado', 'activa'))
            ->when($ver === 'agotadas', fn ($x) => $x->where('estado', 'agotada'))
            ->when($q !== '', fn ($x) => $x->where(fn ($y) => $y->where('cliente_nombre', 'like', "%$q%")->orWhere('codigo', 'like', "%$q%")
                ->orWhere('cliente_telefono', 'like', '%' . (preg_replace('/\D/', '', $q) ?: '~') . '%')))
            ->orderByRaw("estado = 'activa' desc")->orderBy('cliente_nombre')->paginate(40)->withQueryString();
        return view('tiquetera.index', [
            'lista' => $lista, 'ver' => $ver, 'q' => $q,
            'kpi' => [
                'activas' => Tiquetera::where('estado', 'activa')->count(),
                'pendientes' => (int) Tiquetera::where('estado', 'activa')->sum(DB::raw('total - usados')),
                'hoy' => (int) TiqueteraUso::whereIn('tiquetera_id', Tiquetera::select('id'))->where('anulado', false)->whereDate('created_at', today())->sum('cantidad'),
                'mes' => (float) Tiquetera::where('created_at', '>=', now()->startOfMonth())->sum('valor'),
            ],
            'caja' => Caja::abiertaDe(Auth::id()),
        ]);
    }

    public function create()
    {
        return view('tiquetera.create', [
            'metodos' => MetodoPago::where('activo', true)->where('es_credito', false)
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('metodos_pago', 'interno'), fn ($q) => $q->where('interno', false))
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('metodos_pago', 'moneda'), fn ($q) => $q->where(fn ($w) => $w->whereNull('moneda')->orWhere('moneda', '<>', 'VES')))
                ->orderBy('orden')->orderBy('nombre')->get(),
            'planes' => Tiquetera::select('nombre', 'total', 'valor')->latest()->limit(200)->get()->unique(fn ($t) => $t->nombre . '|' . $t->total)->take(8)->values(),
            'caja' => Caja::abiertaDe(Auth::id()),
        ]);
    }

    // Vender una tiquetera: entra a la caja abierta como venta
    public function store(Request $request)
    {
        $caja = Caja::abiertaDe(Auth::id());
        if (!$caja) return redirect()->route('cajas.abrir')->withErrors('Abra su caja para vender la tiquetera.');
        $d = $request->validate([
            'cliente_nombre' => 'required|string|max:80',
            'cliente_telefono' => 'nullable|string|max:20',
            'nombre' => 'required|string|max:80',
            'total' => 'required|integer|min:1|max:1000',
            'valor' => 'required|numeric|min:0|max:999999999',
            'vence' => 'nullable|date|after_or_equal:today',
            'metodo_id' => 'required|integer',
            'nota' => 'nullable|string|max:255',
        ], [
            'cliente_nombre.required' => 'Escriba el nombre del cliente.',
            'nombre.required' => 'Escriba qué incluye (ej. Almuerzo ejecutivo).',
            'total.required' => 'Escriba cuántos almuerzos o servicios incluye.',
        ]);
        $metodo = MetodoPago::where('activo', true)->find($d['metodo_id']);
        if (!$metodo) return back()->withInput()->withErrors('Elija cómo pagó el cliente.');

        $tq = DB::transaction(function () use ($d, $caja, $metodo) {
            $tienda = Tienda::whereKey(TiendaActual::id())->lockForUpdate()->first();
            $tipoDoc = Facturador::tipoDocumento($tienda, false);
            Facturador::asegurarNumeracion($tienda, $tipoDoc);
            $tel = preg_replace('/\D/', '', (string) ($d['cliente_telefono'] ?? ''));
            $clienteId = $tel ? \App\Models\Persona::where('telefono', $tel)->whereHas('cliente')->first()?->cliente?->id : null;
            $valor = round((float) $d['valor'], 2);
            $desc = 'Tiquetera ×' . $d['total'] . ' · ' . $d['nombre'];
            $imp = Impuestos::calcular([['total' => $valor, 'impuesto' => 'ninguno']], 0);

            $venta = PosVenta::create([
                'numero' => $tienda->siguienteNumeroPos(), 'tipo_doc' => $tipoDoc,
                'base_gravable' => $imp['base'], 'impuestos' => $imp['impuestos'], 'impuestos_detalle' => $imp['detalle'] ?: null,
                'caja_id' => $caja->id, 'user_id' => Auth::id(), 'cliente_id' => $clienteId,
                'cliente_nombre' => $d['cliente_nombre'], 'cliente_telefono' => $tel ?: null,
                'subtotal' => $valor, 'descuento' => 0, 'total' => $valor, 'cambio' => 0, 'estado' => 'pagada',
            ]);
            $li = $imp['lineas'][0];
            $venta->items()->create(['impuesto' => $li['impuesto'], 'iva_tasa' => $li['tasa'], 'base' => $li['base'], 'iva_valor' => $li['iva'],
                'producto_variante_id' => null, 'descripcion' => $desc, 'cantidad' => 1, 'precio' => $valor, 'costo' => 0, 'subtotal' => $valor]);
            if ($valor > 0) $venta->pagos()->create(['metodo_pago_id' => $metodo->id, 'monto' => $valor]);

            $n = (int) $tienda->consecutivo_tiquetera + 1;
            $tienda->forceFill(['consecutivo_tiquetera' => $n])->save();
            $tq = Tiquetera::create([
                'codigo' => 'TQ-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT), 'cliente_id' => $clienteId,
                'cliente_nombre' => $d['cliente_nombre'], 'cliente_telefono' => $tel ?: null, 'nombre' => $d['nombre'],
                'total' => $d['total'], 'valor' => $valor, 'pos_venta_id' => $venta->id, 'vence' => $d['vence'] ?? null,
                'nota' => $d['nota'] ?? null, 'user_id' => Auth::id(),
            ]);
            Facturador::enviarDespues(Facturador::registrarVenta($venta));
            return $tq;
        });

        return redirect()->route('tiqueteras.show', $tq)->with('success', '🎟️ Tiquetera ' . $tq->codigo . ' vendida: ' . $tq->total . ' × ' . $tq->nombre . ' para ' . $tq->cliente_nombre . '. Quedó en la caja como venta.');
    }

    public function show(Tiquetera $tiquetera)
    {
        $tiquetera->load(['usos.user:id,name', 'venta:id,numero']);
        return view('tiquetera.show', ['t' => $tiquetera, 'esAdmin' => $this->esAdmin()]);
    }

    // Marcar que el cliente consumió (no se le cobra)
    public function usar(Request $request, Tiquetera $tiquetera)
    {
        $cant = max(1, min(20, (int) $request->input('cantidad', 1)));
        $r = DB::transaction(function () use ($tiquetera, $cant) {
            $t = Tiquetera::whereKey($tiquetera->id)->lockForUpdate()->first();
            if ($t->vencida) throw ValidationException::withMessages(['cantidad' => 'Esta tiquetera venció el ' . $t->vence->format('d/m/Y') . '.']);
            if ($t->quedan < $cant) throw ValidationException::withMessages(['cantidad' => $t->quedan ? 'Solo le quedan ' . $t->quedan . '.' : 'Esta tiquetera ya se gastó toda.']);
            TiqueteraUso::create(['tiquetera_id' => $t->id, 'user_id' => Auth::id(), 'cantidad' => $cant]);
            $t->update(['usados' => $t->usados + $cant, 'estado' => $t->usados + $cant >= $t->total ? 'agotada' : 'activa']);
            return $t;
        });
        $msg = '✅ Listo: ' . $r->cliente_nombre . ($cant > 1 ? ' usó ' . $cant : ' usó 1') . '. ' . ($r->quedan ? 'Le quedan ' . $r->quedan . ' de ' . $r->total . '.' : '¡Se gastó toda la tiquetera! Ofrézcale una nueva.');
        return $request->input('volver') === 'lista' ? redirect()->route('tiqueteras.index', $request->only('q'))->with('success', $msg) : back()->with('success', $msg);
    }

    // Quitar un uso marcado por error: el dueño/administrador siempre; quien lo marcó, en los primeros 15 minutos
    public function anularUso(TiqueteraUso $uso)
    {
        $t = Tiquetera::findOrFail($uso->tiquetera_id);
        abort_unless($this->esAdmin() || ((int) $uso->user_id === (int) Auth::id() && $uso->created_at->gt(now()->subMinutes(15))), 403, 'Solo el dueño o el administrador puede quitar este consumo.');
        if ($uso->anulado) return back();
        DB::transaction(function () use ($uso, $t) {
            $uso->update(['anulado' => true]);
            $usados = max(0, $t->usados - $uso->cantidad);
            $t->update(['usados' => $usados, 'estado' => $usados >= $t->total ? 'agotada' : 'activa']);
        });
        return back()->with('success', 'Consumo quitado. Le quedan ' . $t->fresh()->quedan . '.');
    }
}
