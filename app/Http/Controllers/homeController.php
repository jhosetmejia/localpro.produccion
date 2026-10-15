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
            if (Auth::user()->es_superadmin) {
                return redirect()->route('admin.inicio');
            }
            if (Auth::user()->asesor) {
                return redirect()->route('asesor.index');
            }
            return redirect()->route('login')->withErrors('Su usuario no está asociado a ninguna tienda.');
        }
        if (!$tienda->onboarding_completo) {
            return redirect()->route('bienvenida.index');
        }
        if (!$tienda->activa && !Auth::user()->es_superadmin) {
            Auth::logout();
            return redirect()->route('login')->withErrors('La tienda está suspendida. Comuníquese con LocalPro.');
        }

        if ($tienda->plan_vencido && !Auth::user()->es_superadmin) {
            return redirect()->route('plan.index');
        }

        // Conjuntos residenciales tienen su propio panel de administración
        if ($tienda->es_conjunto) {
            return redirect()->route('conjunto.inicio');
        }
        // Cuenta registrada desde la web, aún sin rol asignado
        if (Auth::user()->roles()->count() === 0) {
            return view('panel.pendiente');
        }

        $hoy = Carbon::today();
        $pos = fn () => PosVenta::where('estado', 'pagada');

        // Vendedores (caja): su propio panel, sin ganancias, con sus ventas y su meta
        $yo = Auth::user();
        if (!$yo->es_superadmin && !$yo->hasAnyRole(['dueno', 'administrador'])) {
            $mias = fn () => $pos()->where('user_id', $yo->id);
            $mes = [$hoy->copy()->startOfMonth(), now()];
            return view('panel.vendedor', [
                'yo' => $yo,
                'ventasHoy' => (float) $mias()->whereDate('created_at', $hoy)->sum('total'),
                'numVentasHoy' => $mias()->whereDate('created_at', $hoy)->count(),
                'ventasMes' => (float) $mias()->whereBetween('created_at', $mes)->sum('total'),
                'numVentasMes' => $mias()->whereBetween('created_at', $mes)->count(),
                'caja' => Caja::abiertaDe($yo->id),
                'ultimas' => PosVenta::where('user_id', $yo->id)->whereDate('created_at', $hoy)->latest()->take(10)->get(),
                'diasRestantes' => (int) $hoy->diffInDays($hoy->copy()->endOfMonth()) + 1,
            ]);
        }

        // Ventas del POS de los últimos 7 días para la gráfica
        $semana = collect(range(6, 0))->map(function ($d) use ($hoy, $pos) {
            $dia = $hoy->copy()->subDays($d);
            return [
                'label' => ucfirst($dia->locale('es')->isoFormat('ddd D')),
                'total' => (float) $pos()->whereDate('created_at', $dia)->sum('total'),
            ];
        });

        // Ganancia de hoy: lo vendido menos el costo de lo vendido y los descuentos
        $ventasHoyIds = $pos()->whereDate('created_at', $hoy)->pluck('id');
        $gananciaHoy = (float) \App\Models\PosVentaItem::whereIn('pos_venta_id', $ventasHoyIds)
            ->selectRaw('COALESCE(SUM(subtotal - costo * cantidad), 0) as g')->value('g')
            - (float) PosVenta::whereIn('id', $ventasHoyIds)->sum('descuento');

        // Equipo de caja con sus metas (solo lo ve el dueño / administrador)
        $equipo = User::role('vendedor')->where('tienda_id', $tienda->id)->orderBy('name')->get()->map(function ($u) use ($pos, $hoy) {
            $u->vendido_dia = (float) $pos()->where('user_id', $u->id)->whereDate('created_at', $hoy)->sum('total');
            $u->vendido_mes = (float) $pos()->where('user_id', $u->id)->whereBetween('created_at', [$hoy->copy()->startOfMonth(), now()])->sum('total');
            $u->num_dia = $pos()->where('user_id', $u->id)->whereDate('created_at', $hoy)->count();
            $u->num_mes = $pos()->where('user_id', $u->id)->whereBetween('created_at', [$hoy->copy()->startOfMonth(), now()])->count();
            return $u;
        });

        return view('panel.index', [
            'equipo' => $equipo,
            'gananciaHoy' => $gananciaHoy,
            'ventasHoy' => (float) $pos()->whereDate('created_at', $hoy)->sum('total'),
            'numVentasHoy' => $pos()->whereDate('created_at', $hoy)->count(),
            'ventasMes' => (float) $pos()->whereBetween('created_at', [$hoy->copy()->startOfMonth(), now()])->sum('total'),
            'unidades' => (float) ProductoVariante::where('estado', 1)->sum('stock'),
            'caja' => Caja::abiertaDe(Auth::id()),
            'semana' => $semana,
            'ultimasVentas' => PosVenta::with('user:id,name')->latest()->take(6)->get(),
            'stockBajo' => ProductoVariante::with('producto:id,nombre,unidad')->where('estado', 1)->whereHas('producto', fn ($q) => $q->where('controla_inventario', true))->where('stock', '<=', 3)->orderBy('stock')->take(6)->get(),
            'conteos' => [
                ['Productos', Producto::count(), \App\Models\Tienda::iconoProducto(), 'productos.index', 'ver-producto'],
                ['Clientes', Cliente::count(), 'fa-people-group', 'clientes.index', 'ver-cliente'],
                ['Proveedores', Proveedore::count(), 'fa-handshake', 'proveedores.index', 'ver-proveedore'],
                ['Categorías', Categoria::count(), 'fa-tags', 'categorias.index', 'ver-categoria'],
                ['Cajas', Caja::count(), 'fa-cash-register', 'cajas.index', 'ver-cajas'],
                ['Usuarios', User::where('tienda_id', $tienda->id)->count(), 'fa-user-shield', 'users.index', 'ver-user'],
            ],
        ]);
    }
}
