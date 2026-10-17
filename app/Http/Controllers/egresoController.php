<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Egreso;
use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

// Egresos del negocio. Vendedor: anota y ve los suyos y los de sus compañeros. Dueño/administrador: ve y anota todos.
class egresoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:registrar-egresos|ver-egresos-todos');
    }

    public function index(Request $request)
    {
        $u = Auth::user();
        $admin = $u->can('ver-egresos-todos');
        $desde = $request->date('desde') ?? now()->startOfMonth();
        $hasta = $request->date('hasta') ?? now();
        $cat = $request->get('categoria');
        $quien = $request->get('quien');

        $base = Egreso::visiblesPara($u)->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);
        $lista = (clone $base)->with(['user:id,name', 'metodo:id,nombre,es_efectivo'])
            ->when($cat && isset(Egreso::CATEGORIAS[$cat]), fn ($q) => $q->where('categoria', $cat))
            ->when($quien === 'yo', fn ($q) => $q->where('user_id', $u->id))
            ->when($admin && $quien === 'vendedores', fn ($q) => $q->where('de_admin', false))
            ->when($admin && $quien === 'admin', fn ($q) => $q->where('de_admin', true))
            ->orderByDesc('fecha')->orderByDesc('id')->paginate(30)->withQueryString();

        return view('egreso.index', [
            'lista' => $lista,
            'admin' => $admin,
            'total' => (float) (clone $base)->sum('valor'),
            'mios' => (float) (clone $base)->where('user_id', $u->id)->sum('valor'),
            'porCategoria' => (clone $base)->selectRaw('categoria, SUM(valor) as total, COUNT(*) as n')->groupBy('categoria')->orderByDesc('total')->get(),
            'metodos' => MetodoPago::activos()->get(['id', 'nombre', 'es_efectivo']),
            'caja' => Caja::abiertaDe($u->id),
            'desde' => $desde, 'hasta' => $hasta, 'cat' => $cat, 'quien' => $quien,
        ]);
    }

    public function store(Request $request)
    {
        $u = Auth::user();
        $datos = $request->validate([
            'concepto' => 'required|string|max:160',
            'categoria' => ['required', Rule::in(array_keys(Egreso::CATEGORIAS))],
            'valor' => 'required|numeric|min:1|max:999999999',
            'fecha' => 'nullable|date|before_or_equal:today',
            'metodo_pago_id' => 'nullable|exists:metodos_pago,id',
            'nota' => 'nullable|string|max:255',
            'soporte' => 'nullable|image|max:6144',
            'de_caja' => 'nullable|boolean',
        ], [], ['concepto' => '¿en qué se gastó?', 'valor' => 'valor', 'soporte' => 'foto del recibo', 'metodo_pago_id' => 'forma de pago']);

        $metodo = !empty($datos['metodo_pago_id']) ? MetodoPago::find($datos['metodo_pago_id']) : null;
        // Si sale en efectivo de la caja abierta, se descuenta de lo que debe haber en caja
        $caja = $metodo?->es_efectivo && !MetodoPago::esElectronico($metodo->nombre) && $request->boolean('de_caja', true) ? Caja::abiertaDe($u->id) : null;

        Egreso::create([
            'user_id' => $u->id,
            'fecha' => $datos['fecha'] ?? today(),
            'concepto' => $datos['concepto'],
            'categoria' => $datos['categoria'],
            'valor' => $datos['valor'],
            'metodo_pago_id' => $metodo?->id,
            'caja_id' => $caja?->id,
            'de_admin' => $u->can('ver-egresos-todos'),
            'soporte_path' => $request->hasFile('soporte') ? $request->file('soporte')->store('egresos', 'public') : null,
            'nota' => $datos['nota'] ?? null,
        ]);

        return back()->with('success', 'Egreso anotado' . ($caja ? ' y descontado del efectivo de su caja.' : '.'));
    }

    public function destroy(Egreso $egreso)
    {
        $u = Auth::user();
        abort_unless($egreso->de_admin ? $u->can('ver-egresos-todos') : true, 404);
        if (!$egreso->puedeBorrar($u)) {
            return back()->withErrors('Este egreso ya no se puede borrar (su caja está cerrada). Pídale al administrador que lo corrija.');
        }
        if ($egreso->soporte_path) {
            Storage::disk('public')->delete($egreso->soporte_path);
        }
        $egreso->delete();
        return back()->with('success', 'Egreso borrado.');
    }
}
