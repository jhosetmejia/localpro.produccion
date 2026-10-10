<?php

namespace App\Http\Controllers;

use App\Models\Tienda;
use App\Models\User;
use App\Support\CrearTienda;
use App\Support\Paises;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// Panel del administrador de LocalPro: todas las tiendas inscritas en la plataforma
class adminTiendaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $tiendas = Tienda::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nombre', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('ciudad', 'like', "%$q%")))
            ->when($request->get('estado'), fn ($w, $e) => $w->where('estado', $e))
            ->latest()
            ->get();

        // Métricas de todas las tiendas en pocas consultas (sin el filtro por tienda)
        $ventas = DB::table('pos_ventas')->where('estado', 'pagada')
            ->groupBy('tienda_id')
            ->select('tienda_id', DB::raw('COUNT(*) as n'), DB::raw('SUM(total) as total'), DB::raw('MAX(created_at) as ultima'))
            ->get()->keyBy('tienda_id');
        $productos = DB::table('productos')->groupBy('tienda_id')->select('tienda_id', DB::raw('COUNT(*) as n'))->pluck('n', 'tienda_id');
        $usuarios = DB::table('users')->whereNotNull('tienda_id')->groupBy('tienda_id')->select('tienda_id', DB::raw('COUNT(*) as n'))->pluck('n', 'tienda_id');
        $duenos = User::role('dueno')->whereIn('tienda_id', $tiendas->pluck('id'))->get()->groupBy('tienda_id');

        $resumen = [
            'total' => Tienda::count(),
            'activas' => Tienda::where('estado', 'activa')->count(),
            'nuevas_mes' => Tienda::where('created_at', '>=', now()->startOfMonth())->count(),
            'configurando' => Tienda::where('onboarding_completo', false)->count(),
            'ventas_hoy' => DB::table('pos_ventas')->where('estado', 'pagada')->whereDate('created_at', today())->count(),
        ];

        return view('admin.tiendas.index', compact('tiendas', 'ventas', 'productos', 'usuarios', 'duenos', 'resumen', 'q'));
    }

    public function create()
    {
        return view('admin.tiendas.create', ['tipos' => Tienda::TIPOS, 'paises' => Paises::LISTA]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:80',
            'tipo_negocio' => ['required', Rule::in(array_keys(Tienda::TIPOS))],
            'pais' => ['required', Rule::in(array_keys(Paises::LISTA))],
            'ciudad' => 'nullable|string|max:60',
            'telefono' => 'nullable|string|max:30',
            'dueno_nombre' => 'required|string|max:80',
            'dueno_email' => 'required|email|max:120|unique:users,email',
            'dueno_telefono' => 'nullable|string|max:20',
            'dueno_password' => 'required|string|min:8',
        ], [], [
            'dueno_nombre' => 'nombre del dueño', 'dueno_email' => 'correo del dueño', 'dueno_password' => 'contraseña del dueño',
        ]);

        [$tienda] = CrearTienda::crear(
            ['nombre' => $datos['nombre'], 'tipo_negocio' => $datos['tipo_negocio'], 'pais' => $datos['pais'], 'ciudad' => $datos['ciudad'] ?? null, 'telefono' => $datos['telefono'] ?? null],
            ['name' => $datos['dueno_nombre'], 'email' => $datos['dueno_email'], 'telefono' => $datos['dueno_telefono'] ?? null, 'password' => $datos['dueno_password']]
        );

        return redirect()->route('admin.tiendas.show', $tienda)
            ->with('success', 'Tienda creada. El dueño entra con ' . $datos['dueno_email'] . ' y la contraseña que usted definió; el sistema le pedirá terminar la configuración.');
    }

    public function show(Tienda $tienda)
    {
        $usuarios = User::where('tienda_id', $tienda->id)->orderBy('name')->get();
        $ventas = DB::table('pos_ventas')->where('tienda_id', $tienda->id)->where('estado', 'pagada');
        $metricas = [
            'ventas' => (clone $ventas)->count(),
            'total' => (float) (clone $ventas)->sum('total'),
            'mes' => (float) (clone $ventas)->where('created_at', '>=', now()->startOfMonth())->sum('total'),
            'productos' => DB::table('productos')->where('tienda_id', $tienda->id)->count(),
            'unidades' => (int) DB::table('producto_variantes')->where('tienda_id', $tienda->id)->sum('stock'),
            'cajas_abiertas' => DB::table('cajas')->where('tienda_id', $tienda->id)->where('estado', 'abierta')->count(),
        ];
        $ultimas = DB::table('pos_ventas')->where('tienda_id', $tienda->id)->latest()->limit(8)->get();

        return view('admin.tiendas.show', compact('tienda', 'usuarios', 'metricas', 'ultimas'));
    }

    public function estado(Tienda $tienda)
    {
        $tienda->update(['estado' => $tienda->activa ? 'suspendida' : 'activa']);
        return back()->with('success', 'La tienda ' . $tienda->nombre . ' quedó ' . ($tienda->activa ? 'activa' : 'suspendida') . '.');
    }

    // Ver el sistema como si fuera la tienda (para dar soporte)
    public function entrar(Request $request, Tienda $tienda)
    {
        $request->session()->put('tienda_visitada', $tienda->id);
        return redirect()->route('panel');
    }

    public function salir(Request $request)
    {
        $request->session()->forget('tienda_visitada');
        return redirect()->route('admin.tiendas.index');
    }
}
