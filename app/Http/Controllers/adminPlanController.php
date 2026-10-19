<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\PagoSuscripcion;
use App\Models\Plan;
use Illuminate\Http\Request;

// Administrador: planes y precios de LocalPro, WhatsApp de soporte e ingresos por suscripciones
class adminPlanController extends Controller
{
    public function index()
    {
        return view('admin.planes.index', [
            'planes' => Plan::withCount(['tiendas' => fn ($q) => $q])->orderBy('orden')->get(),
            'soporte' => Ajuste::valor('soporte_whatsapp'),
            'ejemplos' => Ajuste::valor(asesorEjemplosController::AJUSTE, 'LCT-00003'),
            'paqPrecio' => \App\Support\AmpliarProductos::precio(),
            'paqCantidad' => \App\Support\AmpliarProductos::cantidad(),
            'paqPrecioUsd' => \App\Support\AmpliarProductos::precioUsd(),
            'tasa' => \App\Support\Moneda::tasa(),
            'monedaVe' => strtoupper((string) Ajuste::valor('moneda_venezuela', 'USD')) === 'VES' ? 'VES' : 'USD',
            'tasaVes' => \App\Support\Moneda::tasaVes(),
            'metaEnVivo' => (string) Ajuste::valor('meta_en_vivo') === '1',
            'metaPrueba' => Ajuste::valor('meta_tiendas_prueba'),
            'wompi' => planController::wompiActivo(),
            'ingresosMes' => (float) PagoSuscripcion::where('estado', 'aprobado')->where('updated_at', '>=', now()->startOfMonth())->sum(PagoSuscripcion::sqlEnCop()), // en pesos (los de Venezuela pasados a pesos)
            'pagos' => PagoSuscripcion::with(['tienda:id,nombre', 'plan:id,nombre'])->latest()->limit(20)->get(),
        ]);
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'planes' => 'array',
            'planes.*.nombre' => 'required|string|max:40',
            'planes.*.precio' => 'required|numeric|min:0',
            'planes.*.precio_usd' => 'nullable|numeric|min:0|max:1000000',
            'nuevo.precio_usd' => 'nullable|numeric|min:0|max:1000000',
            'productos_paquete_precio_usd' => 'nullable|numeric|min:0|max:1000000',
            'tasa_usd_cop' => 'nullable|integer|min:1|max:1000000',
            'planes.*.dias' => 'nullable|integer|min:1|max:3650',
            'planes.*.descripcion' => 'nullable|string|max:200',
            'planes.*.max_productos' => 'nullable|integer|min:1|max:1000000',
            'planes.*.max_usuarios' => 'nullable|integer|min:1|max:10000',
            'nuevo.nombre' => 'nullable|string|max:40',
            'nuevo.precio' => 'nullable|numeric|min:0',
            'nuevo.dias' => 'nullable|integer|min:1|max:3650',
            'soporte_whatsapp' => 'nullable|string|max:20',
            'productos_paquete_precio' => 'nullable|integer|min:0|max:100000000',
            'ejemplos_tiendas' => 'nullable|string|max:300',
            'productos_paquete_cantidad' => 'nullable|integer|min:1|max:100000',
            'moneda_venezuela' => 'nullable|in:USD,VES',
            'tasa_ves_usd' => 'nullable|numeric|min:0|max:100000000',
        ], [
            'moneda_venezuela.in' => 'Elija dólares o bolívares para Venezuela.',
        ]);
        if ($request->input('moneda_venezuela') === 'VES' && (float) $request->input('tasa_ves_usd') <= 0) {
            return back()->withErrors(['tasa_ves_usd' => 'Para usar bolívares escriba la tasa: cuántos Bs vale US$1.'])->withInput();
        }
        foreach ((array) $request->input('planes') as $id => $d) {
            Plan::whereKey($id)->update([
                'nombre' => $d['nombre'], 'precio' => $d['precio'], 'precio_usd' => isset($d['precio_usd']) && $d['precio_usd'] !== '' ? round((float) $d['precio_usd'], 2) : null, 'dias' => $d['dias'] ?: null,
                'descripcion' => $d['descripcion'] ?? null, 'activo' => !empty($d['activo']),
                'max_productos' => ($d['max_productos'] ?? null) ?: null, 'max_usuarios' => ($d['max_usuarios'] ?? null) ?: null,
            ]);
        }
        if ($request->filled('nuevo.nombre')) {
            Plan::create([
                'nombre' => $request->input('nuevo.nombre'), 'precio' => $request->input('nuevo.precio', 0), 'precio_usd' => $request->filled('nuevo.precio_usd') ? round((float) $request->input('nuevo.precio_usd'), 2) : null,
                'dias' => $request->input('nuevo.dias') ?: null, 'activo' => true, 'orden' => Plan::max('orden') + 1,
            ]);
        }
        Ajuste::guardar('soporte_whatsapp', preg_replace('/[^\d+]/', '', (string) $request->input('soporte_whatsapp')));
        if ($request->filled('productos_paquete_precio')) Ajuste::guardar('productos_paquete_precio', (int) $request->input('productos_paquete_precio'));
        if ($request->filled('productos_paquete_precio_usd')) Ajuste::guardar('productos_paquete_precio_usd', round((float) $request->input('productos_paquete_precio_usd'), 2));
        if ($request->filled('tasa_usd_cop')) Ajuste::guardar('tasa_usd_cop', (int) $request->input('tasa_usd_cop'));
        // Moneda de Venezuela (dólares o bolívares): cambia todo el sistema para las cuentas de Venezuela
        if ($request->has('tasa_ves_usd')) Ajuste::guardar('tasa_ves_usd', round((float) $request->input('tasa_ves_usd'), 2));
        if ($request->filled('moneda_venezuela')) Ajuste::guardar('moneda_venezuela', $request->input('moneda_venezuela'));
        \App\Support\Moneda::olvidar();
        if ($request->filled('productos_paquete_cantidad')) Ajuste::guardar('productos_paquete_cantidad', (int) $request->input('productos_paquete_cantidad'));
        if ($request->has('ejemplos_tiendas')) Ajuste::guardar(asesorEjemplosController::AJUSTE, mb_substr(strtoupper(preg_replace('/[^A-Za-z0-9,\-]/', '', str_replace([' ', ';'], ',', (string) $request->input('ejemplos_tiendas')))), 0, 300));
        Ajuste::guardar('meta_en_vivo', $request->boolean('meta_en_vivo') ? '1' : '');
        Ajuste::guardar('meta_tiendas_prueba', mb_substr(strtoupper(preg_replace('/[^A-Za-z0-9,\-]/', '', str_replace([' ', ';', "\n"], ',', (string) $request->input('meta_tiendas_prueba')))), 0, 2000));
        return back()->with('success', 'Planes guardados.');
    }
}
