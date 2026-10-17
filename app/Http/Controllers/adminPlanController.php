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
            'metaEnVivo' => (string) Ajuste::valor('meta_en_vivo') === '1',
            'metaPrueba' => Ajuste::valor('meta_tiendas_prueba'),
            'wompi' => planController::wompiActivo(),
            'ingresosMes' => (float) PagoSuscripcion::where('estado', 'aprobado')->where('updated_at', '>=', now()->startOfMonth())->sum('monto'),
            'pagos' => PagoSuscripcion::with(['tienda:id,nombre', 'plan:id,nombre'])->latest()->limit(20)->get(),
        ]);
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'planes' => 'array',
            'planes.*.nombre' => 'required|string|max:40',
            'planes.*.precio' => 'required|numeric|min:0',
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
        ]);
        foreach ((array) $request->input('planes') as $id => $d) {
            Plan::whereKey($id)->update([
                'nombre' => $d['nombre'], 'precio' => $d['precio'], 'dias' => $d['dias'] ?: null,
                'descripcion' => $d['descripcion'] ?? null, 'activo' => !empty($d['activo']),
                'max_productos' => ($d['max_productos'] ?? null) ?: null, 'max_usuarios' => ($d['max_usuarios'] ?? null) ?: null,
            ]);
        }
        if ($request->filled('nuevo.nombre')) {
            Plan::create([
                'nombre' => $request->input('nuevo.nombre'), 'precio' => $request->input('nuevo.precio', 0),
                'dias' => $request->input('nuevo.dias') ?: null, 'activo' => true, 'orden' => Plan::max('orden') + 1,
            ]);
        }
        Ajuste::guardar('soporte_whatsapp', preg_replace('/[^\d+]/', '', (string) $request->input('soporte_whatsapp')));
        if ($request->filled('productos_paquete_precio')) Ajuste::guardar('productos_paquete_precio', (int) $request->input('productos_paquete_precio'));
        if ($request->filled('productos_paquete_cantidad')) Ajuste::guardar('productos_paquete_cantidad', (int) $request->input('productos_paquete_cantidad'));
        if ($request->has('ejemplos_tiendas')) Ajuste::guardar(asesorEjemplosController::AJUSTE, mb_substr(strtoupper(preg_replace('/[^A-Za-z0-9,\-]/', '', str_replace([' ', ';'], ',', (string) $request->input('ejemplos_tiendas')))), 0, 300));
        Ajuste::guardar('meta_en_vivo', $request->boolean('meta_en_vivo') ? '1' : '');
        Ajuste::guardar('meta_tiendas_prueba', mb_substr(strtoupper(preg_replace('/[^A-Za-z0-9,\-]/', '', str_replace([' ', ';', "\n"], ',', (string) $request->input('meta_tiendas_prueba')))), 0, 2000));
        return back()->with('success', 'Planes guardados.');
    }
}
