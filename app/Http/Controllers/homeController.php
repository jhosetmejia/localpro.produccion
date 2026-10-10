<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Caja;
use App\Models\PosVenta;
use App\Models\ProductoVariante;
use App\Models\Producto;
use App\Models\Proveedore;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class homeController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return view('welcome');
        }

        $tienda = \App\Support\TiendaActual::tienda();
        if (!$tienda) {
            return Auth::user()->es_superadmin
                ? redirect()->route('admin.tiendas.index')
                : redirect()->route('login')->withErrors('Su usuario no está asociado a ninguna tienda.');
        }
        if (!$tienda->onboarding_completo) {
            return redirect()->route('bienvenida.index');
        }
        if (!$tienda->activa && !Auth::user()->es_superadmin) {
            Auth::logout();
            return redirect()->route('login')->withErrors('La tienda está suspendida. Comuníquese con LocalPro.');
        }

        // Cuenta registrada desde la web, aún sin rol asignado
        if (Auth::user()->roles()->count() === 0) {
            return view('panel.pendiente');
        }

        $hoy = Carbon::today();
        $pos = fn () => PosVenta::where('estado', 'pagada');

        // Ventas del POS de los últimos 7 días para la gráfica
        $semana = collect(range(6, 0))->map(function ($d) use ($hoy, $pos) {
            $dia = $hoy->copy()->subDays($d);
            return [
                'label' => ucfirst($dia->locale('es')->isoFormat('ddd D')),
                'total' => (float) $pos()->whereDate('created_at', $dia)->sum('total'),
            ];
        });

        return view('panel.index', [
            'ventasHoy' => (float) $pos()->whereDate('created_at', $hoy)->sum('total'),
            'numVentasHoy' => $pos()->whereDate('created_at', $hoy)->count(),
            'ventasMes' => (float) $pos()->whereBetween('created_at', [$hoy->copy()->startOfMonth(), now()])->sum('total'),
            'unidades' => (int) ProductoVariante::where('estado', 1)->sum('stock'),
            'caja' => Caja::abiertaDe(Auth::id()),
            'semana' => $semana,
            'ultimasVentas' => PosVenta::with('user:id,name')->latest()->take(6)->get(),
            'stockBajo' => ProductoVariante::with('producto:id,nombre')->where('estado', 1)->where('stock', '<=', 3)->orderBy('stock')->take(6)->get(),
            'conteos' => [
                ['Productos', Producto::count(), 'fa-shirt', 'productos.index', 'ver-producto'],
                ['Clientes', Cliente::count(), 'fa-people-group', 'clientes.index', 'ver-cliente'],
                ['Proveedores', Proveedore::count(), 'fa-handshake', 'proveedores.index', 'ver-proveedore'],
                ['Categorías', Categoria::count(), 'fa-tags', 'categorias.index', 'ver-categoria'],
                ['Cajas', Caja::count(), 'fa-cash-register', 'cajas.index', 'ver-cajas'],
                ['Usuarios', User::where('tienda_id', $tienda->id)->count(), 'fa-user-shield', 'users.index', 'ver-user'],
            ],
        ]);
    }
}
